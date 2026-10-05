import { ElementDef } from '../schema/element-def';

/**
 * Conversion saisie utilisateur ↔ valeur lexicale XML, et contrôle des
 * facettes XSD.
 *
 * - nombres : virgule ou point acceptés, espaces de milliers retirés ; les
 *   chiffres saisis sont conservés tels quels (aucun arrondi, aucune
 *   reformulation) → précision préservée ;
 * - dates : AAAA-MM-JJ ou JJ/MM/AAAA, sérialisées en AAAA-MM-JJ ;
 * - une saisie qui ne peut pas être représentée dans le type (texte dans un
 *   nombre) est refusée ; une valeur représentable mais hors facette (borne,
 *   motif, énumération) est acceptée et signalée : le brouillon reste
 *   enregistrable.
 */
export type ParseResult = { ok: true; value: string } | { ok: false; error: string };

export function isNumeric(def: ElementDef | null): boolean {
  return !!def?.base && ['double', 'decimal', 'float', 'int', 'integer', 'long', 'short', 'nonNegativeInteger', 'positiveInteger'].includes(def.base);
}

export function isInteger(def: ElementDef | null): boolean {
  return !!def?.base && ['int', 'integer', 'long', 'short', 'nonNegativeInteger', 'positiveInteger'].includes(def.base);
}

export function parseInput(input: string, def: ElementDef | null): ParseResult {
  const base = def?.base ?? 'string';
  if (isNumeric(def)) {
    const compact = input.trim().replace(/[\s  ]/g, '');
    if (compact === '') return { ok: false, error: 'Valeur vide : utilisez « Effacer » pour retirer la valeur.' };
    const normalized = compact.replace(',', '.');
    if (isInteger(def)) {
      if (!/^[+-]?\d+$/.test(normalized)) return { ok: false, error: 'Nombre entier attendu.' };
      return { ok: true, value: normalized };
    }
    if (!/^[+-]?(\d+(\.\d*)?|\.\d+)([eE][+-]?\d+)?$/.test(normalized)) {
      return { ok: false, error: 'Nombre attendu (la virgule décimale est acceptée).' };
    }
    return { ok: true, value: normalized };
  }
  if (base === 'date') {
    const v = input.trim();
    const fr = /^(\d{1,2})\/(\d{1,2})\/(\d{4})$/.exec(v);
    const iso = fr ? `${fr[3]}-${fr[2].padStart(2, '0')}-${fr[1].padStart(2, '0')}` : v;
    if (!/^\d{4}-\d{2}-\d{2}$/.test(iso) || Number.isNaN(Date.parse(iso + 'T00:00:00Z'))) {
      return { ok: false, error: 'Date attendue (JJ/MM/AAAA ou AAAA-MM-JJ).' };
    }
    return { ok: true, value: iso };
  }
  if (base === 'dateTime') {
    const v = input.trim();
    if (!/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2}(\.\d+)?)?(Z|[+-]\d{2}:\d{2})?$/.test(v)) {
      return { ok: false, error: 'Date et heure ISO 8601 attendues (AAAA-MM-JJThh:mm:ss).' };
    }
    return { ok: true, value: v };
  }
  return { ok: true, value: input };
}

/** Écarts aux facettes XSD d'une valeur lexicale (liste vide si conforme). */
export function facetViolations(value: string, def: ElementDef | null): string[] {
  if (!def || def.kind !== 'simple') return [];
  const out: string[] = [];
  const f = def.facets;
  if (isNumeric(def)) {
    const n = Number(value);
    if (Number.isNaN(n)) return [isInteger(def) ? 'Nombre entier attendu.' : 'Nombre attendu.'];
    if (isInteger(def) && !/^[+-]?\d+$/.test(value)) out.push('Nombre entier attendu.');
    if (f.minInclusive !== undefined && n < Number(f.minInclusive)) out.push(`Valeur inférieure au minimum (${fr(f.minInclusive)}).`);
    if (f.maxInclusive !== undefined && n > Number(f.maxInclusive)) out.push(`Valeur supérieure au maximum (${fr(f.maxInclusive)}).`);
    if (f.minExclusive !== undefined && n <= Number(f.minExclusive)) out.push(`Valeur strictement supérieure à ${fr(f.minExclusive)} attendue.`);
    if (f.maxExclusive !== undefined && n >= Number(f.maxExclusive)) out.push(`Valeur strictement inférieure à ${fr(f.maxExclusive)} attendue.`);
  }
  if (f.enumeration && !f.enumeration.includes(value)) out.push(`Valeur non autorisée (valeurs admises : ${f.enumeration.join(', ')}).`);
  if (f.length !== undefined && [...value].length !== Number(f.length)) out.push(`Longueur attendue : ${f.length} caractères.`);
  if (f.minLength !== undefined && [...value].length < Number(f.minLength)) out.push(`Au moins ${f.minLength} caractères attendus.`);
  if (f.maxLength !== undefined && [...value].length > Number(f.maxLength)) out.push(`Au plus ${f.maxLength} caractères.`);
  if (f.pattern && !f.pattern.some((p) => xsdPatternMatches(p, value))) out.push(`Format attendu non respecté (motif ${f.pattern.join(' ou ')}).`);
  if (def.enumLabels && !f.enumeration && !(value in def.enumLabels)) out.push(`Code « ${value} » absent de la liste officielle.`);
  return out;
}

/** Les motifs XSD sont implicitement ancrés ; leur syntaxe usuelle est compatible JS. */
function xsdPatternMatches(pattern: string, value: string): boolean {
  try {
    return new RegExp(`^(?:${pattern})$`, 'u').test(value);
  } catch {
    return true;
  }
}

function fr(n: string): string {
  return n.replace('.', ',');
}

/** Affichage français d'une valeur lexicale (nombre : virgule décimale). */
export function displayValue(raw: string, def: ElementDef | null): string {
  if (def?.enumLabels && raw in def.enumLabels) {
    const l = def.enumLabels[raw];
    return l.charAt(0).toUpperCase() + l.slice(1);
  }
  if (isNumeric(def) && raw.trim() !== '' && !Number.isNaN(Number(raw))) return raw.trim().replace('.', ',');
  if (def?.base === 'date' && /^\d{4}-\d{2}-\d{2}$/.test(raw)) {
    const [y, m, d] = raw.split('-');
    return `${d}/${m}/${y}`;
  }
  return raw;
}

/** Affichage arrondi d'un résultat numérique (lecture seule ; la valeur stockée n'est pas modifiée). */
export function displayRounded(raw: string, digits = 2): string {
  const n = Number(raw);
  if (raw.trim() === '' || Number.isNaN(n)) return raw;
  return n.toLocaleString('fr-FR', { maximumFractionDigits: digits });
}
