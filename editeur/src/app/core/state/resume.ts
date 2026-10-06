/**
 * Résumé d'un brouillon pour la liste d'accueil : adresse, type de DPE,
 * surface, étiquettes. Lu dans le XML sérialisé par expressions simples,
 * sans construire le DOM de chaque brouillon.
 */
export interface ResumeDossier {
  adresse: string | null;
  type: string | null;
  surface: number | null;
  /** nombre d'appartements d'un DPE immeuble */
  logements: number | null;
  classeEnergie: string | null;
  classeClimat: string | null;
}

const MAISON = ['1'];
const APPARTEMENT = ['2', '3', '4', '5', '31', '32', '35', '36', '37'];
const IMMEUBLE = ['6', '7', '8', '9', '26', '27', '28', '29', '30'];
const GENERE = ['10', '11', '12', '13', '33', '34', '38', '39', '40'];

export function typeDpe(methode: string | null, racine: string | null): string | null {
  if (racine === 'logement_neuf') return 'Logement neuf';
  if (racine === 'tertiaire') return 'Tertiaire';
  if (!methode) return null;
  if (MAISON.includes(methode)) return 'Maison';
  if (APPARTEMENT.includes(methode)) return 'Appartement';
  if (IMMEUBLE.includes(methode)) return 'Immeuble';
  if (GENERE.includes(methode)) return 'Appartement (issu de l\'immeuble)';
  return 'Issu d\'une étude thermique';
}

export function resumeDossier(xml: string): ResumeDossier {
  const tag = (name: string, scope = xml): string | null => {
    const m = new RegExp(`<${name}(?:\\s[^>]*)?>([^<]*)</${name}>`).exec(scope);
    const v = m ? decode(m[1]).trim() : '';
    return v === '' ? null : v;
  };
  const num = (v: string | null) => (v !== null && Number.isFinite(Number(v.replace(',', '.'))) ? Number(v.replace(',', '.')) : null);
  const bloc = /<adresse_bien(?:\s[^>]*)?>([\s\S]*?)<\/adresse_bien>/.exec(xml)?.[1] ?? '';
  const racine = /<dpe(?:\s[^>]*)?>[\s\S]*?<(logement_neuf|tertiaire|logement)[\s>]/.exec(xml)?.[1] ?? null;
  const methode = tag('enum_methode_application_dpe_log_id');
  const immeuble = methode !== null && IMMEUBLE.includes(methode);
  return {
    adresse: tag('ban_label', bloc) ?? tag('label_brut_avec_complement', bloc) ?? tag('label_brut', bloc) ?? tag('adresse_brut', bloc),
    type: typeDpe(methode, racine),
    surface: num(immeuble ? tag('surface_habitable_immeuble') : (tag('surface_habitable_logement') ?? tag('surface_habitable_immeuble'))),
    logements: immeuble ? num(tag('nombre_appartement')) : null,
    classeEnergie: tag('classe_bilan_dpe'),
    classeClimat: tag('classe_emission_ges'),
  };
}

function decode(s: string): string {
  return s.replace(/&(amp|lt|gt|quot|apos|#\d+|#x[0-9a-f]+);/gi, (m, e: string) =>
    e === 'amp' ? '&' : e === 'lt' ? '<' : e === 'gt' ? '>' : e === 'quot' ? '"' : e === 'apos' ? '\'' :
      e[1] === 'x' || e[1] === 'X' ? String.fromCodePoint(parseInt(e.slice(2), 16)) : String.fromCodePoint(parseInt(e.slice(1), 10)));
}
