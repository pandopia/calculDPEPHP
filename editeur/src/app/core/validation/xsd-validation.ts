import { isObservatoireHeader } from '../format/format-detector';
import { fieldMeta } from '../metier/field-meta';
import { Dossier } from '../state/dossier';
import { indexedPath } from '../xml/safe-xml';
import { serialize } from '../xml/serializer';
import { getUid } from '../xml/uid';
import { ownerObject, relativePath } from '../edition/doc-ops';
import { issue, Issue } from './issues';

/**
 * Validation XSD par libxml2 (xmllint compilé en WebAssembly), entièrement
 * locale. Le document validé est l'export de dépôt : identifiants internes
 * retirés et en-tête de l'observatoire (numero_dpe, statut) exclu, puisqu'il
 * n'appartient pas au XSD de dépôt. Chaque erreur est rattachée, par son
 * numéro de ligne, au nœud du document de travail.
 *
 * Une validation XSD réussie ne signifie ni que le DPE est recalculé, ni
 * qu'il est accepté par l'ADEME.
 */
export interface XsdEngine {
  validate(xml: string, xsd: string): Promise<{ valid: boolean; errors: { line: number | null; message: string }[] }>;
}

export interface XsdReport {
  ranAt: string;
  revision: number;
  xsd: string | null;
  valid: boolean | null;
  issues: Issue[];
  excludedHeader: boolean;
  message: string;
}

export async function runXsdValidation(d: Dossier, engine: XsdEngine): Promise<XsdReport> {
  const base = { ranAt: new Date().toISOString(), revision: d.revision, xsd: d.schema?.fileName ?? null, excludedHeader: d.format.enTeteObservatoire };
  if (!d.schema) {
    return { ...base, valid: null, issues: [], message: 'Aucun XSD associé à la version de ce fichier : validation impossible.' };
  }
  const { text, lineToElement } = serialize(d.working, { omit: isObservatoireHeader });
  const result = await engine.validate(text, d.schema.xsdText);
  const issues = result.errors.map((e) => {
    const el = e.line !== null ? lineToElement.get(e.line) ?? null : null;
    const owner = el ? (ownerObject(el) ?? el.ownerDocument.documentElement) : null;
    return issue({
      niveau: 'xsd', gravite: 'erreur',
      uid: owner ? getUid(owner) : null,
      field: el && owner && owner !== el ? relativePath(owner, el) : undefined,
      xmlPath: el ? indexedPath(el) : undefined,
      message: translateXsdMessage(e.message),
      detail: `Ligne ${e.line ?? '?'} de l'export : ${e.message}`,
    });
  });
  return {
    ...base, valid: result.valid, issues,
    message: result.valid
      ? `Conforme au schéma ${d.schema.fileName} (${d.schema.schemaVersion}).`
      : `${issues.length} écart(s) au schéma ${d.schema.fileName}.`,
  };
}

/** Traduction des messages libxml2 les plus fréquents. */
export function translateXsdMessage(raw: string): string {
  const msg = raw.replace(/^Schemas validity error\s*:\s*/, '').trim();
  const el = /^Element '([^']+)'(?:, attribute '([^']+)')?: (.*)$/s.exec(msg);
  if (!el) return msg;
  const [, name, attr, rest] = el;
  const who = attr ? `Attribut « ${attr} » de « ${label(name)} »` : `« ${label(name)} »`;
  let m: RegExpExecArray | null;
  if ((m = /^This element is not expected\. Expected is (?:one of )?\( (.*) \)\.?$/.exec(rest))) {
    return `${who} : balise inattendue à cet endroit (attendu : ${list(m[1])}).`;
  }
  if (/^This element is not expected\.?$/.test(rest)) return `${who} : balise inattendue à cet endroit.`;
  if ((m = /^Missing child element\(s\)\. Expected is (?:one of )?\( (.*) \)\.?$/.exec(rest))) {
    return `${who} : éléments obligatoires manquants (${list(m[1])}).`;
  }
  if ((m = /^\[facet 'pattern'\] The value '(.*)' is not accepted by the pattern '(.*)'\.?$/.exec(rest))) {
    return `${who} : la valeur « ${m[1]} » ne respecte pas le format attendu (${m[2]}).`;
  }
  if ((m = /^\[facet 'enumeration'\] The value '(.*)' is not an element of the set \{(.*)\}\.?$/.exec(rest))) {
    return `${who} : la valeur « ${m[1]} » n'est pas autorisée (valeurs admises : ${m[2].replace(/'/g, '')}).`;
  }
  if ((m = /^\[facet '(min|max)(Inclusive|Exclusive)'\] The value '(.*)' is (?:less|greater) than (?:or equal to )?(?:the )?(?:minimum|maximum) value (?:allowed )?\('(.*)'\)\.?$/.exec(rest))) {
    const borne = m[1] === 'min' ? 'minimum' : 'maximum';
    return `${who} : la valeur ${m[3]} dépasse le ${borne} autorisé (${m[4]}).`;
  }
  if ((m = /^\[facet '(length|minLength|maxLength)'\] The value has a length of '(\d+)'; this (?:differs from|underruns|exceeds) the allowed (?:minimum |maximum )?length of '(\d+)'\.?$/.exec(rest))) {
    return `${who} : longueur ${m[2]} caractère(s), attendu ${m[1] === 'length' ? '' : m[1] === 'minLength' ? 'au moins ' : 'au plus '}${m[3]}.`;
  }
  if ((m = /^'(.*)' is not a valid value of the atomic type '(?:xs:)?(\w+)'\.?$/.exec(rest))) {
    const types: Record<string, string> = { double: 'un nombre', int: 'un nombre entier', date: 'une date AAAA-MM-JJ', dateTime: 'une date et heure ISO 8601' };
    return `${who} : « ${m[1]} » n'est pas ${types[m[2]] ?? 'une valeur valide (' + m[2] + ')'}.`;
  }
  if ((m = /^'(.*)' is not a valid value of the local atomic type\.?$/.exec(rest))) return `${who} : « ${m[1]} » n'est pas une valeur valide.`;
  if (/^The attribute '([^']+)' is required but missing\.?$/.test(rest)) {
    return `${who} : attribut obligatoire « ${/'([^']+)'/.exec(rest)![1]} » manquant.`;
  }
  if (/is not a valid value of the atomic type/.test(rest) || /^\[facet/.test(rest)) return `${who} : valeur non conforme (${rest}).`;
  return `${who} : ${rest}`;
}

function label(name: string): string {
  return fieldMeta(name, null).label;
}

function list(s: string): string {
  return s.split(',').map((x) => x.trim()).filter(Boolean).map(label).join(', ');
}

/** Moteur navigateur : worker xmllint-wasm servi depuis /xmllint/. */
export class BrowserXsdEngine implements XsdEngine {
  constructor(private readonly baseUrl = 'xmllint/') {}

  validate(xml: string, xsd: string): Promise<{ valid: boolean; errors: { line: number | null; message: string }[] }> {
    return new Promise((resolve, reject) => {
      const worker = new Worker(new URL(this.baseUrl + 'xmllint-browser.mjs', document.baseURI), { type: 'module' });
      const key = 'xmllint-wasm';
      worker.addEventListener('message', (event: MessageEvent) => {
        const data = event.data as { exitCode: number; stderr: string } & Record<string, unknown>;
        if (!data || data[key] !== true) return;
        worker.terminate();
        if (data.exitCode !== 0 && data.exitCode !== 3 && data.exitCode !== 4) {
          reject(new Error(data.stderr || 'Échec du validateur XSD.'));
          return;
        }
        resolve({ valid: data.exitCode === 0, errors: data.exitCode === 0 ? [] : parseXmllintErrors(data.stderr) });
      });
      worker.addEventListener('error', (e) => {
        worker.terminate();
        reject(new Error(e.message || 'Validateur XSD indisponible.'));
      });
      worker.postMessage({
        [key]: true,
        inputFiles: [
          { fileName: 'dossier.xml', contents: xml },
          { fileName: 'schema.xsd', contents: xsd },
        ],
        args: ['--schema', 'schema.xsd', '--noout', 'dossier.xml'],
        initialMemory: 256,
        maxMemory: 16384,
      });
    });
  }
}

export function parseXmllintErrors(stderr: string): { line: number | null; message: string }[] {
  return stderr
    .split('\n')
    .map((l) => l.trim())
    .filter((l) => l && !l.endsWith(' validates') && !/fails to validate$/.test(l))
    .map((l) => {
      const m = /^[^:]+:(\d+):\s*(.*)$/.exec(l);
      return m ? { line: Number(m[1]), message: m[2] } : { line: null, message: l };
    });
}
