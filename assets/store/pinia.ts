/**
 * One Pinia instance for the whole app. Exported so bootstrap code can install
 * it on the Vue app and still use the same stores when wiring fetch hooks.
 */
import { createPinia } from "pinia";

export const pinia = createPinia();
export default pinia;
