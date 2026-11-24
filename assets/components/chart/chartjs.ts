import { Chart as ChartJS, registerables } from "chart.js";
import ChartDataLabels from "chartjs-plugin-datalabels";

// PrimeVue's Chart wrapper used to register Chart.js internals for us. With the
// wrapper gone, register the controllers/scales/elements and the shared
// datalabels plugin once for every direct Chart.js consumer.
ChartJS.register(...registerables, ChartDataLabels);

export { ChartJS };
