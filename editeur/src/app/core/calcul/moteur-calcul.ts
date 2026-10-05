/**
 * Moteur de calcul DPE.
 *
 * Le moteur reçoit le XML de travail exporté et rend le XML complet (balises
 * `donnee_intermediaire` et `sortie` calculées). L'éditeur n'en reprend que
 * les zones de résultats, après avoir vérifié que les données d'entrée
 * revenues sont identiques à celles envoyées (voir integrer-resultats.ts).
 */
export interface MoteurCalcul {
  readonly nom: string;
  readonly description: string;
  /** hôte destinataire, affiché à l'utilisateur avant tout envoi */
  readonly destination: string | null;
  disponible(): boolean;
  calculer(xmlTravail: string): Promise<string>;
}

export class MoteurCalculError extends Error {}

export const AUCUN_MOTEUR: MoteurCalcul = {
  nom: 'Aucun moteur connecté',
  description: "Aucun moteur de calcul n'est connecté : les résultats ne sont pas recalculés après modification.",
  destination: null,
  disponible: () => false,
  calculer: () => Promise.reject(new MoteurCalculError('Aucun moteur de calcul connecté.')),
};

/**
 * Moteur de calcul Pandopia : POST du XML ADEME, réponse XML ADEME calculée.
 *   curl -X POST -H 'Content-Type: application/xml' --data-binary @dpe.xml \
 *        https://app.pandopia.com/api/calculdpe/xmlademe
 */
export class MoteurPandopia implements MoteurCalcul {
  readonly nom = 'Moteur de calcul Pandopia';
  readonly description = 'Calcul 3CL-2021 par le service Pandopia : le XML de travail est envoyé à ce service, qui renvoie les consommations, émissions, coûts et étiquettes.';
  readonly destination: string;

  constructor(
    private readonly url = 'https://app.pandopia.com/api/calculdpe/xmlademe',
    private readonly timeoutMs = 60000,
    private readonly fetcher: typeof fetch = (...a) => fetch(...a),
  ) {
    this.destination = new URL(url).host;
  }

  disponible(): boolean {
    return true;
  }

  async calculer(xmlTravail: string): Promise<string> {
    const ctrl = new AbortController();
    const timer = setTimeout(() => ctrl.abort(), this.timeoutMs);
    let res: Response;
    try {
      res = await this.fetcher(this.url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/xml' },
        body: xmlTravail,
        signal: ctrl.signal,
      });
    } catch (e) {
      throw new MoteurCalculError(
        ctrl.signal.aborted
          ? `Le moteur de calcul n'a pas répondu en ${this.timeoutMs / 1000} s.`
          : `Moteur de calcul injoignable (${this.destination}) : vérifiez la connexion.`,
      );
    } finally {
      clearTimeout(timer);
    }
    const text = await res.text();
    if (!res.ok) {
      const detail = text.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 300);
      throw new MoteurCalculError(`Le moteur de calcul a refusé le dossier (HTTP ${res.status})${detail ? ' : ' + detail : '.'}`);
    }
    if (!text.trimStart().startsWith('<')) {
      throw new MoteurCalculError('Réponse du moteur de calcul inattendue (XML attendu).');
    }
    return text;
  }
}
