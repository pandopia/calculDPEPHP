/**
 * Point d'extension pour un moteur de calcul DPE.
 *
 * Le premier livrable n'en embarque aucun : l'éditeur ne fabrique pas de
 * recalcul approximatif. Un moteur branché ici recevra le XML de travail
 * exporté et devra rendre un XML complet (balises `donnee_intermediaire` et
 * `sortie`), qui serait alors réimporté comme un nouveau fichier source —
 * jamais fusionné en silence.
 */
export interface MoteurCalcul {
  readonly nom: string;
  readonly description: string;
  disponible(): boolean;
  calculer(xmlTravail: string): Promise<string>;
}

export const AUCUN_MOTEUR: MoteurCalcul = {
  nom: 'Aucun moteur connecté',
  description:
    "Les résultats affichés sont ceux du fichier source. Aucun moteur de calcul n'est connecté : ils ne sont pas recalculés après modification.",
  disponible: () => false,
  calculer: () => Promise.reject(new Error('Aucun moteur de calcul connecté.')),
};
