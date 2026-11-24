import globals from "globals";
import js from "@eslint/js";
import pluginVue from "eslint-plugin-vue";
import vueParser from "vue-eslint-parser";
import tseslint from "typescript-eslint";
import prettierPlugin from "eslint-plugin-prettier";
import prettierConfig from "eslint-config-prettier";

export default tseslint.config(
  {
    ignores: ["node_modules/", "public/build/", "vendor/"]
  },
  js.configs.recommended,
  {
    languageOptions: {
      globals: {
        ...globals.browser,
        ...globals.node
      }
    }
  },
  ...pluginVue.configs["flat/essential"],
  {
    rules: {
      // Upstream Sakai uses single-word view and PrimeVue component names.
      "vue/multi-word-component-names": "off",
      "vue/no-reserved-component-names": "off"
    }
  },
  {
    files: ["**/*.vue"],
    languageOptions: {
      parser: vueParser,
      parserOptions: {
        parser: tseslint.parser,
        ecmaVersion: "latest",
        sourceType: "module"
      }
    },
    plugins: {
      "@typescript-eslint": tseslint.plugin
    },
    rules: {
      ...tseslint.configs.recommended.rules
    }
  },
  {
    files: ["**/*.ts"],
    languageOptions: {
      parser: tseslint.parser
    },
    rules: {
      ...tseslint.configs.recommended.rules
    }
  },
  prettierConfig,
  {
    plugins: {
      prettier: prettierPlugin
    },
    rules: {
      "prettier/prettier": "warn"
    }
  }
);
