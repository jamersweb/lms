import { usePage } from '@inertiajs/vue3';
import en from '../lang/en.json';
import enRoman from '../lang/en_roman.json';
import ur from '../lang/ur.json';

const messages = {
  en,
  en_roman: enRoman,
  ur,
};

export function useI18n() {
  const page = usePage();
  const locale = (page?.props?.locale) || 'en';
  const bundle = messages[locale] || messages.en;

  const t = (key, replacements = {}) => {
    const parts = key.split('.');
    let value = parts.reduce((acc, part) => (acc && acc[part] !== undefined ? acc[part] : undefined), bundle);

    if (typeof value !== 'string') {
      // Fallback: return the key itself so missing translations are visible
      value = key;
    }

    // Simple :placeholder replacement
    Object.keys(replacements).forEach((placeholder) => {
      value = value.replace(`:${placeholder}`, replacements[placeholder]);
    });

    return value;
  };

  return { t, locale };
}

