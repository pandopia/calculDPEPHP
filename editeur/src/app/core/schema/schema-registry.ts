import { ElementDef } from './element-def';
import { XsdCompiler } from './xsd-compiler';

/**
 * Associe chaque `administratif/enum_version_id` au XSD officiel de l'ADEME.
 *
 * Source : dépôt de l'observatoire DPE (gitlab.com/observatoire-dpe/
 * observatoire-dpe, dossier modele_donnee), copié dans public/schemas/.
 * Le nom des fichiers ne suit pas la version du modèle : la correspondance
 * est établie sur la liste xs:enumeration de `enum_version_id` et sur
 * l'en-tête de version de chaque XSD.
 */
export const VERSION_TO_XSD: Record<string, string> = {
  '1': 'DPEv1-obsolete.xsd', // V6.4.0 — 2022-04-15
  '1.1': 'DPEv1-obsolete.xsd',
  '2': 'DPEv2.xsd', // V7.1.0 — 2022-09-28
  '2.1': 'DPEv2.xsd',
  '2.2': 'DPEv2.xsd',
  '2.3': 'DPEv2.2.xsd', // V8.0.4 — 2023-05-16
  '2.4': 'DPEv2.3.xsd', // V8.2.0 — 2024-05-20
  '2.5': 'DPEv2.4.xsd', // V9.1.1 — 2025-04-30
  '2.6': 'DPEv2.6.xsd', // V9.2.3 — 2026-03-31
};

/** Versions proposées à la création d'un dossier : seule la version en vigueur. */
export const VERSIONS_CREATION = ['2.6'];
export const VERSIONS_OBSOLETES = ['1', '1.1'];

export class SchemaHandle {
  private index = new Map<string, ElementDef>();

  constructor(
    readonly fileName: string,
    readonly xsdText: string,
    readonly root: ElementDef,
    readonly schemaVersion: string,
  ) {
    const walk = (def: ElementDef) => {
      this.index.set(def.path, def);
      def.children.forEach(walk);
    };
    walk(root);
  }

  def(path: string): ElementDef | null {
    return this.index.get(path) ?? null;
  }

  defFor(el: Element): ElementDef | null {
    return this.def(schemaPath(el));
  }

  label(): string {
    return `${this.fileName} — ${this.schemaVersion}`;
  }
}

/** Chemin XSD (sans indice) d'un élément : `dpe/logement/enveloppe/...`. */
export function schemaPath(el: Element): string {
  const parts: string[] = [];
  for (let n: Element | null = el; n; n = n.parentElement) parts.unshift(n.localName);
  return parts.join('/');
}

export type XsdSource = (fileName: string) => Promise<string>;

export class SchemaRegistry {
  private handles = new Map<string, Promise<SchemaHandle>>();

  constructor(private readonly source: XsdSource) {}

  static xsdFor(version: string | null): string | null {
    return version !== null ? (VERSION_TO_XSD[version] ?? null) : null;
  }

  forVersion(version: string | null): Promise<SchemaHandle | null> {
    const file = SchemaRegistry.xsdFor(version);
    if (!file) return Promise.resolve(null);
    let handle = this.handles.get(file);
    if (!handle) {
      handle = this.source(file).then((text) => {
        const { root, version: v } = new XsdCompiler().compile(text);
        return new SchemaHandle(file, text, root, v);
      });
      this.handles.set(file, handle);
    }
    return handle;
  }
}
