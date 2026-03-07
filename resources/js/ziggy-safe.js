/**
 * Wrapper around ziggy-js that sanitizes null/undefined route params
 * to prevent "Cannot read properties of null (reading 'toString')" errors.
 * Use direct path to avoid circular ref when ziggy-js is aliased to this file.
 */
// Import from node_modules directly to avoid circular ref when ziggy-js is aliased to this file
import { route as ziggyRoute, ZiggyVue, useRoute } from '../../node_modules/ziggy-js/dist/index.esm.js';


function safeRoute(name, params, absolute) {
  if (params != null && typeof params === 'object' && !Array.isArray(params)) {
    const sanitized = {};
    for (const [k, v] of Object.entries(params)) {
      sanitized[k] = v == null ? '' : v;
    }
    return ziggyRoute(name, sanitized, absolute);
  }
  return ziggyRoute(name, params, absolute);
}

export { ZiggyVue, useRoute };
export { safeRoute as route };
