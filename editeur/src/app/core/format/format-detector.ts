import { SchemaHandle, SchemaRegistry, VERSIONS_OBSOLETES } from '../schema/schema-registry';

/**
 * Détection de la famille et de la version d'un XML DPE, sans rien modifier.
 *
 * Familles (racine + branche du xs:choice de `dpe`) :
 * - DPE logement existant 3CL-2021 (`dpe/logement`) : périmètre supporté ;
 * - DPE logement neuf RT2012/RE2020 (`dpe/logement_neuf`) : vue générique ;
 * - DPE tertiaire 2006 (`dpe/tertiaire`) : vue générique ;
 * - audit énergétique (racine `audit`) : refusé (autre schéma).
 *
 * L'export public de l'observatoire ADEME ajoute `numero_dpe` et `statut`
 * avant `administratif` : ces balises ne sont pas dans le XSD de dépôt.
 */
export type Famille = 'dpe_logement_existant' | 'dpe_logement_neuf' | 'dpe_tertiaire' | 'audit' | 'inconnue';
export type NiveauSupport = 'complet' | 'generique' | 'aucun';

export interface FormatInfo {
  racine: string;
  famille: Famille;
  libelleFamille: string;
  niveauSupport: NiveauSupport;
  branche: string | null;
  attributVersion: string | null;
  enumVersionId: string | null;
  libelleVersion: string | null;
  enumModeleDpeId: string | null;
  libelleModele: string | null;
  methodeApplication: string | null;
  libelleMethodeApplication: string | null;
  dateEtablissement: string | null;
  numeroDpe: string | null;
  enTeteObservatoire: boolean;
  resultatsPresents: boolean;
  xsd: string | null;
  xsdVersion: string | null;
  messages: string[];
}

const LIBELLES: Record<Famille, string> = {
  dpe_logement_existant: 'DPE logement existant (méthode 3CL-2021)',
  dpe_logement_neuf: 'DPE logement neuf (RT2012 / RE2020)',
  dpe_tertiaire: 'DPE tertiaire (méthode 2006)',
  audit: 'Audit énergétique',
  inconnue: 'Format non reconnu',
};

export async function detectFormat(doc: Document, schemas: SchemaRegistry): Promise<{ info: FormatInfo; schema: SchemaHandle | null }> {
  const root = doc.documentElement;
  const text = (path: string): string | null => {
    let el: Element | null = root;
    for (const part of path.split('/')) {
      el = el ? (Array.from(el.children).find((c) => c.localName === part) ?? null) : null;
    }
    const v = el?.textContent?.trim() ?? '';
    return v === '' ? null : v;
  };
  const info: FormatInfo = {
    racine: root.localName,
    famille: 'inconnue',
    libelleFamille: '',
    niveauSupport: 'aucun',
    branche: null,
    attributVersion: root.getAttribute('version'),
    enumVersionId: text('administratif/enum_version_id'),
    libelleVersion: null,
    enumModeleDpeId: text('administratif/enum_modele_dpe_id'),
    libelleModele: null,
    methodeApplication: text('logement/caracteristique_generale/enum_methode_application_dpe_log_id'),
    libelleMethodeApplication: null,
    dateEtablissement: text('administratif/date_etablissement_dpe'),
    numeroDpe: text('numero_dpe'),
    enTeteObservatoire: Array.from(root.children).some((c) => c.localName === 'numero_dpe' || c.localName === 'statut'),
    resultatsPresents: Array.from(root.children).some((b) => Array.from(b.children).some((c) => c.localName === 'sortie')),
    xsd: null,
    xsdVersion: null,
    messages: [],
  };
  const done = (schema: SchemaHandle | null) => {
    info.libelleFamille = LIBELLES[info.famille];
    return { info, schema };
  };

  if (root.localName !== 'dpe') {
    info.famille = root.localName === 'audit' ? 'audit' : 'inconnue';
    info.messages.push(
      root.localName === 'audit'
        ? "Audit énergétique réglementaire : ce schéma (audit.xsd) n'est pas pris en charge."
        : `Racine <${root.localName}> inattendue : un XML DPE commence par <dpe>.`,
    );
    return done(null);
  }

  const branches = ['logement', 'logement_neuf', 'tertiaire'].filter((b) => Array.from(root.children).some((c) => c.localName === b));
  const schema = await schemas.forVersion(info.enumVersionId);
  if (schema) {
    info.xsd = schema.fileName;
    info.xsdVersion = schema.schemaVersion;
    info.libelleVersion = schema.def('dpe/administratif/enum_version_id')?.enumLabels?.[info.enumVersionId ?? ''] ?? null;
    info.libelleModele = schema.def('dpe/administratif/enum_modele_dpe_id')?.enumLabels?.[info.enumModeleDpeId ?? ''] ?? null;
    info.libelleMethodeApplication =
      schema.def('dpe/logement/caracteristique_generale/enum_methode_application_dpe_log_id')?.enumLabels?.[info.methodeApplication ?? ''] ?? null;
  }

  if (branches.length !== 1) {
    info.niveauSupport = 'aucun';
    info.messages.push(
      branches.length === 0
        ? 'Aucune branche logement, logement_neuf ou tertiaire : contenu DPE non reconnu.'
        : `Plusieurs branches (${branches.join(', ')}) : le schéma n'en autorise qu'une.`,
    );
    return done(schema);
  }
  info.branche = branches[0];
  info.famille = info.branche === 'logement' ? 'dpe_logement_existant' : info.branche === 'logement_neuf' ? 'dpe_logement_neuf' : 'dpe_tertiaire';

  if (info.famille !== 'dpe_logement_existant') {
    info.niveauSupport = 'generique';
    info.messages.push(
      `${LIBELLES[info.famille]} : hors périmètre. Ouverture en vue générique (structure du schéma), sans fiches métier ni règles dédiées.`,
    );
  } else if (info.enumVersionId === null) {
    info.niveauSupport = 'generique';
    info.messages.push('Version du modèle (administratif/enum_version_id) absente : aucun schéma ne peut être associé. Ouverture sans validation XSD.');
  } else if (!schema) {
    info.niveauSupport = 'generique';
    info.messages.push(
      `Version de modèle « ${info.enumVersionId} » inconnue : aucun XSD correspondant. Le fichier n'est pas migré ; ouverture sans validation XSD.`,
    );
  } else {
    info.niveauSupport = 'complet';
    if (VERSIONS_OBSOLETES.includes(info.enumVersionId)) {
      info.messages.push(
        `Version ${info.enumVersionId} du modèle (juillet–décembre 2021), déclarée obsolète par l'ADEME : validation contre ${schema.fileName}.`,
      );
    }
  }
  if (info.enumModeleDpeId && info.enumModeleDpeId !== '1' && info.famille === 'dpe_logement_existant') {
    info.messages.push(`Modèle de DPE ${info.enumModeleDpeId} déclaré avec une branche « logement » : incohérence à vérifier.`);
  }
  if (info.enTeteObservatoire) {
    info.messages.push(
      "Export de l'observatoire ADEME : numero_dpe et statut, ajoutés par l'observatoire, ne font pas partie du XSD de dépôt. Ils sont conservés tels quels et exclus de la validation XSD.",
    );
  }
  if (info.dateEtablissement && info.dateEtablissement < '2021-07-01') {
    info.messages.push("Date d'établissement antérieure au 1er juillet 2021 : hors du périmètre de la méthode 3CL-2021.");
  }
  return done(schema);
}

/** Balises de l'en-tête observatoire, hors XSD de dépôt. */
export function isObservatoireHeader(el: Element): boolean {
  return el.parentElement === el.ownerDocument.documentElement && (el.localName === 'numero_dpe' || el.localName === 'statut');
}
