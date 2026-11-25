const assert = require("node:assert/strict");
const fs = require("node:fs");
const path = require("node:path");
const test = require("node:test");
const {
  parse,
  compileScript,
  compileTemplate,
} = require("@vue/compiler-sfc");

function collectVueFiles(directory, files = []) {
  for (const entry of fs.readdirSync(directory, { withFileTypes: true })) {
    const entryPath = path.join(directory, entry.name);
    if (entry.isDirectory()) {
      collectVueFiles(entryPath, files);
    }
    else if (entry.name.endsWith(".vue")) {
      files.push(entryPath);
    }
  }
  return files;
}

test("Vue SFCs compile without compiler warnings", () => {
  const assets = path.join(process.cwd(), "assets");
  const files = collectVueFiles(assets);
  assert.ok(files.length > 0, "expected Vue single-file components under assets/");

  const warnings = [];
  const onWarn = (warning) => warnings.push(warning.message ?? String(warning));
  const originalWarn = console.warn;

  // Some compiler-sfc warnings (e.g. imported compiler macros) bypass onWarn.
  console.warn = (message) => {
    if (String(message).includes("[@vue/compiler-sfc]")) {
      warnings.push(String(message));
    }
  };

  try {
    for (const file of files) {
      const source = fs.readFileSync(file, "utf8");
      const { descriptor, errors } = parse(source, { filename: file });
      assert.deepEqual(errors, [], `${file}: parse errors`);

      if (descriptor.script || descriptor.scriptSetup) {
        compileScript(descriptor, { id: file, onWarn });
      }

      if (descriptor.template) {
        compileTemplate({
          source: descriptor.template.content,
          filename: file,
          id: file,
          compilerOptions: { onWarn },
        });
      }
    }
  }
  finally {
    console.warn = originalWarn;
  }

  assert.deepEqual(warnings, [], "unexpected Vue compiler warnings");
});
