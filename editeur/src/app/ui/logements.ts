import { Component, computed, inject, signal } from '@angular/core';
import { DossierService } from '../services/dossier.service';
import { NavService } from '../services/nav.service';
import {
  contexteImmeuble, controler, empreinte, liaisonsUtiles, liaisonsVides, Logement, logementsManquants, logementsVisites,
  nettoyerLiaisons, Paroi, parois, POSITIONS, preRemplir, TypeLiaison, TYPES_LIAISON, TYPOLOGIES, UNITE,
} from '../core/immeuble/logements';

type Vue = 'logements' | 'liaisons' | 'resultats';

const fmt = (n: number | null | undefined, d = 0) => (n === null || n === undefined ? '—' : n.toLocaleString('fr-FR', { maximumFractionDigits: d }));

/**
 * DPE des logements générés depuis le DPE immeuble (méthode 3CL §17.2.2) :
 * description des logements, liaison de chacun aux parois sur lesquelles il
 * donne, répartition du chauffage, calcul par le service et résultats.
 */
@Component({
  selector: 'app-logements',
  template: `
    @let d = svc.dossier()!;
    @let c = ctx();
    @let cfg = config();
    <div class="logements">
      <div class="card">
        <div class="card-head">
          <h2>DPE des logements <span class="muted small">générés à partir du DPE immeuble (3CL §17.2.2)</span></h2>
          <button class="primary" (click)="svc.calculerLogements()" [disabled]="svc.logementsEnCours() || erreurs().length > 0"
            [title]="erreurs().length ? 'À corriger : ' + erreurs()[0].message : 'Calcule l\\'immeuble puis le DPE de chaque logement'">
            {{ svc.logementsEnCours() ? 'Calcul en cours…' : 'Calculer les DPE logements' }}
          </button>
        </div>
        <p class="muted small">Chaque logement est décrit (surface, position) puis relié aux parois sur lesquelles il donne. Le moteur en déduit le besoin de chauffage de chaque logement, donc sa part des consommations de l'immeuble. Ces informations sont conservées dans le brouillon : le XSD ADEME ne les prévoit pas, elles ne figurent pas dans le XML exporté.</p>
        @if (c.nonSupporte) {
          <p class="error-box">{{ c.nonSupporte }}</p>
        } @else {
          <div class="facts">
            <div class="fact"><span class="fact-l">Appartements déclarés</span><span class="fact-v">{{ c.nombreAppartements ?? '—' }}</span></div>
            <div class="fact"><span class="fact-l">Surface habitable de l'immeuble</span><span class="fact-v">{{ fmt(c.surfaceImmeuble, 2) }} m²</span></div>
            <div class="fact"><span class="fact-l">Chauffage</span><span class="fact-v">{{ c.chauffage }}</span></div>
            <div class="fact"><span class="fact-l">Eau chaude sanitaire</span><span class="fact-v">{{ c.ecs }}</span></div>
          </div>
          <div class="repartition">
            @if (c.chauffage === 'individuel') {
              <p class="small">Chauffage individuel : consommations réparties selon le besoin de chauffage de chaque logement (coefficient d'individualisation = 1, §17.2.2.2.2). Suppose une gestion homogène des lots attestée par le propriétaire.</p>
            } @else if (c.chauffage === 'collectif') {
              <div class="small">
                <strong>Frais de chauffage individualisés (répartiteurs, compteurs) ?</strong>
                <label class="inline"><input type="radio" name="ifc" [checked]="cfg.repartitionChauffage === 1" (change)="setRepartition(1)" /> Oui — part au besoin de chauffage (§17.2.2.2.2)</label>
                <label class="inline"><input type="radio" name="ifc" [checked]="cfg.repartitionChauffage === 2" (change)="setRepartition(2)" /> Non — au prorata des surfaces (§17.2.2.2.1)</label>
                @if (cfg.repartitionChauffage === 1) {
                  <label class="inline">Coefficient d'individualisation
                    <input class="num-input" type="number" min="0" max="1" step="0.05" placeholder="0,7" [value]="cfg.coefIfc ?? ''" (change)="setCoef($any($event.target).value)" />
                    <span class="muted">vide : 0,7 par défaut (valeur du syndic ou du propriétaire sinon)</span></label>
                }
              </div>
            }
            <p class="small">ECS {{ c.ecs }} : consommations réparties selon le besoin d'eau chaude de chaque logement (nombre d'occupants conventionnel, §11.1).</p>
            @if (utiles()) {
              <label class="check small"><input type="checkbox" [checked]="cfg.approximerLiaisons" (change)="setApprox($any($event.target).checked)" />
                <span>Répartir par approximation les parois reliées à aucun logement (mur support, rez-de-chaussée ou dernier étage, sinon tous les logements). Hypothèse signalée dans les résultats, non prévue par la 3CL.</span></label>
            }
          </div>
        }
      </div>

      <div class="filters sub-tabs">
        <button class="chip-btn" [class.active]="vue() === 'logements'" (click)="vue.set('logements')">Logements {{ cfg.logements.length }}</button>
        <button class="chip-btn" [class.active]="vue() === 'liaisons'" (click)="vue.set('liaisons')" [disabled]="!cfg.logements.length">Liaisons aux parois</button>
        <button class="chip-btn" [class.active]="vue() === 'resultats'" (click)="vue.set('resultats')">Résultats @if (d.resultatsLogements) { {{ d.resultatsLogements.logements.length }} }</button>
      </div>

      @if (controles().length && vue() !== 'resultats') {
        <ul class="issues compact-issues">
          @for (i of controles().slice(0, plusControles() ? 999 : 6); track $index) {
            <li [class]="'g-' + i.gravite">{{ i.message }}
              @if (i.logementId) { <button class="link small" (click)="ouvrir(i.logementId)">voir</button> }</li>
          }
          @if (controles().length > 6) { <li><button class="link small" (click)="plusControles.set(!plusControles())">{{ plusControles() ? 'Réduire' : '+ ' + (controles().length - 6) + ' autre(s)' }}</button></li> }
        </ul>
      }

      @switch (vue()) {
        @case ('logements') {
          <div class="card">
            <div class="card-head">
              <h3>Logements de l'immeuble</h3>
              <span class="calc-actions">
                @if (visitesDispo().length) { <button class="small" (click)="reprendreVisites()">Reprendre les {{ visitesDispo().length }} logement(s) visité(s) du XML</button> }
                @if (manquants() > 0) { <button class="small" (click)="completer()">Créer les {{ manquants() }} logement(s) manquant(s)</button> }
                <button class="primary small" (click)="ajouter()">+ Un logement</button>
              </span>
            </div>
            @if (cfg.logements.length) {
              <table class="table compact lgt-table">
                <thead><tr><th>Référence</th><th>Description</th><th class="num">Surface (m²)</th><th>Position</th><th>Typologie</th><th>Visité</th><th class="num">Parois</th><th></th></tr></thead>
                <tbody>
                  @for (l of cfg.logements; track l.id) {
                    <tr [class.has-error]="erreurLogement(l.id)">
                      <td><input type="text" [value]="l.reference" (change)="champ(l, 'reference', $any($event.target).value)" /></td>
                      <td><input type="text" [value]="l.description" (change)="champ(l, 'description', $any($event.target).value)" /></td>
                      <td class="num"><input class="num-input" type="number" min="0" step="0.01" [value]="l.surface ?? ''" (change)="champ(l, 'surface', $any($event.target).value)" /></td>
                      <td><select (change)="champ(l, 'position', $any($event.target).value)">
                        <option value="" [selected]="l.position === null">—</option>
                        @for (p of positions; track p.k) { <option [value]="p.k" [selected]="l.position === p.k">{{ p.v }}</option> }
                      </select></td>
                      <td><select (change)="champ(l, 'typologie', $any($event.target).value)">
                        <option value="" [selected]="l.typologie === null">—</option>
                        @for (t of typologies; track t.k) { <option [value]="t.k" [selected]="l.typologie === t.k">{{ t.v }}</option> }
                      </select></td>
                      <td><input type="checkbox" [checked]="l.visite" (change)="champ(l, 'visite', $any($event.target).checked)" /></td>
                      <td class="num"><button class="link" (click)="ouvrir(l.id)">{{ nbLiaisons(l) }}</button></td>
                      <td class="nowrap">
                        <button class="link small" (click)="dupliquer(l)" title="Dupliquer (mêmes liaisons)">⧉</button>
                        <button class="link danger small" (click)="supprimer(l)" title="Supprimer">✕</button>
                      </td>
                    </tr>
                  }
                </tbody>
                <tfoot><tr><td colspan="2" class="muted">Total</td><td class="num" [class.err-text]="ecartSurface()">{{ fmt(surfaceTotale(), 2) }}</td>
                  <td colspan="5" class="muted small">@if (ecartSurface()) { surface de l'immeuble : {{ fmt(c.surfaceImmeuble, 2) }} m² }</td></tr></tfoot>
              </table>
            } @else {
              <p class="muted">Aucun logement. Créez-les d'un coup à partir du nombre d'appartements déclaré, ou un par un.</p>
            }
          </div>
        }
        @case ('liaisons') {
          <div class="card">
            <div class="card-head">
              <span class="filters">
                <button class="chip-btn" [class.active]="mode() === 'logement'" (click)="mode.set('logement')">Par logement</button>
                <button class="chip-btn" [class.active]="mode() === 'paroi'" (click)="mode.set('paroi')">Par paroi</button>
              </span>
              <span class="calc-actions">
                <button class="small" (click)="preRemplir()" title="Planchers bas → logements du rez-de-chaussée ; planchers hauts → du dernier étage ; baies et portes → logements reliés à leur paroi support">Pré-remplir</button>
                @if (obsoletes()) { <button class="small" (click)="nettoyer()">Retirer {{ obsoletes() }} liaison(s) obsolète(s)</button> }
              </span>
            </div>
            @if (!utiles()) { <p class="info-box small">Avec la répartition choisie, les liaisons ne sont pas utilisées par le calcul.</p> }
            @if (mode() === 'logement') {
              <div class="split">
                <ul class="obj-list">
                  @for (l of cfg.logements; track l.id) {
                    <li [class.selected]="logementSel()?.id === l.id" (click)="selLogement.set(l.id)">
                      <div class="ol-head"><span class="ol-title">{{ l.reference || '(sans référence)' }}</span>
                        @if (!nbLiaisons(l)) { <span class="dot miss" title="Aucune paroi reliée">0</span> }</div>
                      <div class="ol-sub"><span>{{ fmt(l.surface, 2) }} m²</span><span>{{ l.position ? positionsMap[l.position] : 'position ?' }}</span><span>{{ nbLiaisons(l) }} paroi(s)</span></div>
                    </li>
                  }
                </ul>
                @if (logementSel(); as l) {
                  <div>
                    <div class="card-head">
                      <h3>{{ l.reference }} <span class="muted small">{{ fmt(l.surface, 2) }} m²</span></h3>
                      @if (cfg.logements.length > 1) {
                        <label class="inline small">Copier les liaisons de
                          <select (change)="copierDe(l, $any($event.target).value); $any($event.target).value = ''">
                            <option value="">…</option>
                            @for (o of cfg.logements; track o.id) { @if (o.id !== l.id) { <option [value]="o.id">{{ o.reference }}</option> } }
                          </select></label>
                      }
                    </div>
                    @for (t of types; track t.key) {
                      @if (paroisPar()[t.key].length) {
                        <h4>{{ t.label }}</h4>
                        <table class="table compact">
                          <tbody>
                            @for (p of paroisPar()[t.key]; track p.uid ?? p.nom) {
                              <tr>
                                <td class="w1"><input type="checkbox" [disabled]="!p.reference" [checked]="!!p.reference && l.liaisons[t.key].includes(p.reference)" (change)="lier(l.id, p, $any($event.target).checked)" /></td>
                                <td><button class="link" (click)="voirParoi(p)">{{ p.nom }}</button> @if (!p.reference) { <span class="err-text small">sans référence</span> }</td>
                                <td class="muted">{{ p.orientation ?? '' }}</td>
                                <td class="num">{{ fmt(p.surface, 2) }} {{ unite[p.type] }}</td>
                                <td class="muted small">{{ partage(p) }}</td>
                              </tr>
                            }
                          </tbody>
                        </table>
                      }
                    }
                  </div>
                }
              </div>
            } @else {
              <div class="split">
                <ul class="obj-list">
                  @for (t of types; track t.key) {
                    @for (p of paroisPar()[t.key]; track p.uid ?? p.nom) {
                      <li [class.selected]="paroiSel()?.uid === p.uid" (click)="selParoi.set(p.uid)">
                        <div class="ol-head"><span class="ol-title">{{ p.nom }}</span>
                          @if (!nbLogementsParoi(p) && !p.suitParois) { <span class="dot miss" title="Reliée à aucun logement">0</span> }</div>
                        <div class="ol-sub"><span>{{ t.label }}</span><span>{{ p.orientation ?? '' }}</span><span>{{ fmt(p.surface, 2) }} {{ unite[p.type] }}</span><span>{{ partage(p) }}</span></div>
                      </li>
                    }
                  }
                </ul>
                @if (paroiSel(); as p) {
                  <div>
                    <div class="card-head">
                      <h3>{{ p.nom }} <span class="muted small">{{ p.orientation ?? '' }} {{ fmt(p.surface, 2) }} {{ unite[p.type] }}</span></h3>
                      @if (p.reference) {
                        <span class="calc-actions">
                          <button class="small" (click)="lierTous(p, true)">Tous</button>
                          @for (pos of positions; track pos.k) { <button class="small" (click)="lierPosition(p, pos.k)">{{ pos.v }}</button> }
                          <button class="small" (click)="lierTous(p, false)">Aucun</button>
                        </span>
                      }
                    </div>
                    @if (p.type === 'pont_thermique') {
                      <p class="info-box small">@if (p.suitParois) { Ce pont thermique est rattaché à ses parois dans le XML : il suit les logements de ces parois. Une liaison directe ci-dessous prime sur ce rattachement. }
                        @else { Pont thermique sans parois associées dans le XML (reference_1/reference_2) : cochez les logements qu'il borde. Sa longueur est répartie entre eux au prorata des surfaces. }</p>
                    }
                    @if (!p.reference) { <p class="error-box small">Paroi sans référence : renseignez sa référence pour la relier.</p> }
                    @else {
                      <table class="table compact">
                        <tbody>
                          @for (l of cfg.logements; track l.id) {
                            <tr>
                              <td class="w1"><input type="checkbox" [checked]="l.liaisons[p.type].includes(p.reference)" (change)="lier(l.id, p, $any($event.target).checked)" /></td>
                              <td>{{ l.reference }}</td>
                              <td class="muted">{{ l.position ? positionsMap[l.position] : '' }}</td>
                              <td class="num">{{ fmt(l.surface, 2) }} m²</td>
                            </tr>
                          }
                        </tbody>
                      </table>
                    }
                  </div>
                }
              </div>
            }
          </div>
        }
        @case ('resultats') {
          @let r = d.resultatsLogements;
          @if (!r) {
            <div class="card"><p class="muted">Aucun DPE logement calculé pour l'instant.</p></div>
          } @else {
            <div class="calc-bar" [class.stale]="perime()">
              <span>Calculés par {{ r.moteur }} le {{ date(r.date) }}.
                @if (perime()) { <strong class="warn-text">Le dossier ou les logements ont changé depuis : à recalculer.</strong> }</span>
            </div>
            <div class="card">
              <table class="table compact lgt-table">
                <thead><tr><th>Logement</th><th class="num">Surface</th><th>Énergie</th><th>Climat</th><th class="num">kWh EF/an</th><th class="num">kg CO₂/an</th><th class="num">€/an</th><th></th></tr></thead>
                <tbody>
                  @if (r.batiment.synthese; as b) {
                    <tr class="row-immeuble">
                      <td><strong>Immeuble</strong></td><td class="num">{{ fmt(b.surface, 1) }} m²</td>
                      <td><span class="classe" [attr.data-c]="b.classeEnergie">{{ b.classeEnergie ?? '?' }}</span> {{ fmt(b.epM2) }} kWh/m²</td>
                      <td><span class="classe climat" [attr.data-c]="b.classeClimat">{{ b.classeClimat ?? '?' }}</span> {{ fmt(b.gesM2) }} kg/m²</td>
                      <td class="num">{{ fmt(b.ef) }}</td><td class="num">{{ fmt(b.ges) }}</td><td class="num">{{ fmt(b.cout) }}</td><td></td>
                    </tr>
                  } @else if (r.batiment.erreur) {
                    <tr><td><strong>Immeuble</strong></td><td colspan="7" class="err-text">{{ r.batiment.erreur }}</td></tr>
                  }
                  @for (l of r.logements; track l.reference) {
                    <tr>
                      <td>{{ l.reference }}</td>
                      @if (l.synthese; as s) {
                        <td class="num">{{ fmt(s.surface, 1) }} m²</td>
                        <td><span class="classe" [attr.data-c]="s.classeEnergie">{{ s.classeEnergie ?? '?' }}</span> {{ fmt(s.epM2) }} kWh/m²</td>
                        <td><span class="classe climat" [attr.data-c]="s.classeClimat">{{ s.classeClimat ?? '?' }}</span> {{ fmt(s.gesM2) }} kg/m²</td>
                        <td class="num">{{ fmt(s.ef) }}</td><td class="num">{{ fmt(s.ges) }}</td><td class="num">{{ fmt(s.cout) }}</td>
                        <td class="nowrap">
                          <button class="small" (click)="svc.pdfLogement(l.reference)" [disabled]="!!svc.pdfLogementEnCours() || perime()" [title]="perime() ? 'Résultats à recalculer avant le PDF' : 'Rapport PDF du DPE de ce logement'">{{ svc.pdfLogementEnCours() === l.reference ? 'PDF…' : 'PDF' }}</button>
                          @if (svc.xmlLogements().has(l.reference)) { <button class="small" (click)="svc.xmlLogement(l.reference)" title="XML ADEME calculé du logement">XML</button> }
                        </td>
                      } @else {
                        <td colspan="7" class="err-text">{{ l.erreur ?? 'Résultat absent.' }}</td>
                      }
                    </tr>
                  }
                </tbody>
                @if (totaux(); as t) {
                  <tfoot><tr><td class="muted">Somme des logements</td><td class="num">{{ fmt(t.surface, 1) }} m²</td><td></td><td></td>
                    <td class="num">{{ fmt(t.ef) }}</td><td class="num">{{ fmt(t.ges) }}</td><td class="num">{{ fmt(t.cout) }}</td><td></td></tr></tfoot>
                }
              </table>
              @if (r.hypotheses.length) {
                <h4>Hypothèses du calcul</h4>
                <ul class="small">@for (h of r.hypotheses; track $index) { <li>{{ h }}</li> }</ul>
              }
            </div>
          }
        }
      }
    </div>
  `,
})
export class LogementsComponent {
  protected readonly svc = inject(DossierService);
  private readonly nav = inject(NavService);
  protected readonly fmt = fmt;
  protected readonly types = TYPES_LIAISON;
  protected readonly positions = Object.entries(POSITIONS).map(([k, v]) => ({ k: Number(k), v }));
  protected readonly positionsMap = POSITIONS;
  protected readonly unite = UNITE;
  protected readonly typologies = Object.entries(TYPOLOGIES).map(([k, v]) => ({ k: Number(k), v }));
  protected readonly vue = signal<Vue>('logements');
  protected readonly mode = signal<'logement' | 'paroi'>('paroi');
  protected readonly selLogement = signal<string | null>(null);
  protected readonly selParoi = signal<string | null>(null);
  protected readonly plusControles = signal(false);

  constructor() {
    if (this.svc.dossier()?.resultatsLogements) this.vue.set('resultats');
  }

  protected readonly ctx = computed(() => {
    this.svc.revision();
    return contexteImmeuble(this.svc.dossier()!.working);
  });
  protected readonly config = computed(() => {
    this.svc.revision();
    return this.svc.dossier()!.immeuble();
  });
  protected readonly listeParois = computed(() => {
    this.svc.revision();
    const d = this.svc.dossier()!;
    return parois(d.working, d.schema);
  });
  protected readonly paroisPar = computed(() => {
    const out = Object.fromEntries(TYPES_LIAISON.map((t) => [t.key, [] as Paroi[]])) as Record<TypeLiaison, Paroi[]>;
    for (const p of this.listeParois()) out[p.type].push(p);
    return out;
  });
  protected readonly utiles = computed(() => liaisonsUtiles(this.config(), this.ctx()));
  protected readonly controles = computed(() => controler(this.config(), this.ctx(), this.listeParois()));
  protected readonly erreurs = computed(() => this.controles().filter((c) => c.gravite === 'erreur'));
  protected readonly manquants = computed(() => Math.max(0, (this.ctx().nombreAppartements ?? 0) - this.config().logements.length));
  protected readonly surfaceTotale = computed(() => this.config().logements.reduce((s, l) => s + (l.surface ?? 0), 0));
  protected readonly ecartSurface = computed(() => this.ctx().surfaceImmeuble !== null && Math.abs(this.surfaceTotale() - this.ctx().surfaceImmeuble!) > 0.01);
  protected readonly visitesDispo = computed(() => {
    this.svc.revision();
    const deja = new Set(this.config().logements.filter((l) => l.visite).map((l) => l.description));
    let n = 0;
    return logementsVisites(this.svc.dossier()!.working, () => String(n++)).filter((l) => !deja.has(l.description));
  });
  protected readonly obsoletes = computed(() => nettoyerLiaisons(structuredClone(this.config()), this.listeParois()));
  /** nombre de logements reliés à chaque paroi, par « type:référence » */
  private readonly usage = computed(() => {
    const m = new Map<string, number>();
    for (const l of this.config().logements) for (const t of TYPES_LIAISON) for (const r of l.liaisons[t.key]) m.set(`${t.key}:${r}`, (m.get(`${t.key}:${r}`) ?? 0) + 1);
    return m;
  });
  protected readonly logementSel = computed<Logement | null>(() => {
    const ls = this.config().logements;
    return ls.find((l) => l.id === this.selLogement()) ?? ls[0] ?? null;
  });
  protected readonly paroiSel = computed<Paroi | null>(() => {
    const ps = this.listeParois();
    return ps.find((p) => p.uid === this.selParoi()) ?? ps[0] ?? null;
  });
  protected readonly perime = computed(() => {
    this.svc.revision();
    const d = this.svc.dossier()!;
    const r = d.resultatsLogements;
    return !!r && r.empreinte !== empreinte(d.exportXml(), d.immeuble());
  });
  protected readonly totaux = computed(() => {
    const ls = this.svc.dossier()!.resultatsLogements?.logements.map((l) => l.synthese).filter((s) => !!s) ?? [];
    if (!ls.length) return null;
    const sum = (f: (s: (typeof ls)[number]) => number | null) => ls.reduce((n, s) => n + (f(s) ?? 0), 0);
    return { surface: sum((s) => s!.surface), ef: sum((s) => s!.ef), ges: sum((s) => s!.ges), cout: sum((s) => s!.cout) };
  });

  protected erreurLogement(id: string): boolean {
    return this.erreurs().some((c) => c.logementId === id);
  }

  protected nbLiaisons(l: Logement): number {
    return TYPES_LIAISON.reduce((n, t) => n + l.liaisons[t.key].length, 0);
  }

  protected nbLogementsParoi(p: Paroi): number {
    return p.reference ? (this.usage().get(`${p.type}:${p.reference}`) ?? 0) : 0;
  }

  protected partage(p: Paroi): string {
    const n = this.nbLogementsParoi(p);
    return n ? `${n} logement(s)` : p.suitParois ? 'suit ses parois' : 'aucun logement';
  }

  protected date(iso: string): string {
    return new Date(iso).toLocaleString('fr-FR', { dateStyle: 'short', timeStyle: 'short' });
  }

  // ------------------------------------------------------------ modifications

  private newId(): string {
    return 'lg' + Date.now().toString(36) + Math.random().toString(36).slice(2, 7);
  }

  protected setRepartition(v: 1 | 2): void {
    this.svc.modifierImmeuble('Répartition du chauffage', (cfg) => { cfg.repartitionChauffage = v; });
  }

  protected setCoef(v: string): void {
    const n = v.trim() === '' ? null : Number(v.replace(',', '.'));
    this.svc.modifierImmeuble('Coefficient d\'individualisation', (cfg) => { cfg.coefIfc = n === null || Number.isNaN(n) ? null : n; });
  }

  protected setApprox(v: boolean): void {
    this.svc.modifierImmeuble('Approximation des liaisons', (cfg) => { cfg.approximerLiaisons = v; });
  }

  protected ajouter(): void {
    this.svc.modifierImmeuble('Ajout d\'un logement', (cfg) => {
      const pris = new Set(cfg.logements.map((l) => l.reference));
      let n = cfg.logements.length + 1;
      while (pris.has(`Lot ${n}`)) n++;
      cfg.logements.push({ id: this.newId(), reference: `Lot ${n}`, description: '', surface: null, position: null, typologie: null, visite: false, liaisons: liaisonsVides() });
    });
  }

  protected completer(): void {
    this.svc.modifierImmeuble('Création des logements', (cfg) => { cfg.logements.push(...logementsManquants(cfg, this.ctx(), () => this.newId())); },
      'Logements créés : surface restante répartie à parts égales, à ajuster.');
  }

  protected reprendreVisites(): void {
    const v = this.visitesDispo().map((l) => ({ ...l, id: this.newId() }));
    this.svc.modifierImmeuble('Reprise des logements visités', (cfg) => { cfg.logements.push(...v); });
  }

  protected champ(l: Logement, champ: 'reference' | 'description' | 'surface' | 'position' | 'typologie' | 'visite', raw: string | boolean): void {
    this.svc.modifierImmeuble(`Logement ${l.reference}`, (cfg) => {
      const x = cfg.logements.find((o) => o.id === l.id)!;
      if (champ === 'visite') x.visite = raw as boolean;
      else if (champ === 'reference' || champ === 'description') x[champ] = String(raw).trim();
      else {
        const n = String(raw).trim() === '' ? null : Number(String(raw).replace(',', '.'));
        x[champ] = n === null || Number.isNaN(n) ? null : n;
      }
    });
  }

  protected dupliquer(l: Logement): void {
    this.svc.modifierImmeuble(`Duplication de ${l.reference}`, (cfg) => {
      const pris = new Set(cfg.logements.map((o) => o.reference));
      let ref = `${l.reference} (copie)`;
      for (let i = 2; pris.has(ref); i++) ref = `${l.reference} (copie ${i})`;
      const i = cfg.logements.findIndex((o) => o.id === l.id);
      cfg.logements.splice(i + 1, 0, { ...structuredClone(l), id: this.newId(), reference: ref, visite: false });
    });
  }

  protected supprimer(l: Logement): void {
    this.svc.modifierImmeuble(`Suppression de ${l.reference}`, (cfg) => { cfg.logements = cfg.logements.filter((o) => o.id !== l.id); });
  }

  protected lier(logementId: string, p: Paroi, on: boolean): void {
    if (!p.reference) return;
    this.svc.modifierImmeuble(`Liaison ${p.nom}`, (cfg) => {
      const l = cfg.logements.find((o) => o.id === logementId)!;
      const set = new Set(l.liaisons[p.type]);
      if (on) set.add(p.reference!); else set.delete(p.reference!);
      l.liaisons[p.type] = [...set];
    });
  }

  protected lierTous(p: Paroi, on: boolean): void {
    this.lierSi(p, () => on, on ? `${p.nom} reliée à tous les logements` : `${p.nom} déliée`);
  }

  protected lierPosition(p: Paroi, position: number): void {
    this.lierSi(p, (l) => l.position === position || l.liaisons[p.type].includes(p.reference!), `${p.nom} : ${POSITIONS[position].toLowerCase()}`);
  }

  private lierSi(p: Paroi, f: (l: Logement) => boolean, label: string): void {
    if (!p.reference) return;
    this.svc.modifierImmeuble(label, (cfg) => {
      for (const l of cfg.logements) {
        const set = new Set(l.liaisons[p.type]);
        if (f(l)) set.add(p.reference!); else set.delete(p.reference!);
        l.liaisons[p.type] = [...set];
      }
    });
  }

  protected copierDe(l: Logement, sourceId: string): void {
    if (!sourceId) return;
    this.svc.modifierImmeuble(`Liaisons de ${l.reference}`, (cfg) => {
      const src = cfg.logements.find((o) => o.id === sourceId)!;
      cfg.logements.find((o) => o.id === l.id)!.liaisons = structuredClone(src.liaisons);
    });
  }

  protected preRemplir(): void {
    const n = this.svc.modifierImmeuble('Pré-remplissage des liaisons', (cfg) => preRemplir(cfg, this.listeParois()));
    if (n !== undefined) this.svc.toast('info', n ? `${n} liaison(s) ajoutée(s), à vérifier.` : 'Aucune liaison à ajouter (positions des logements et murs support à renseigner d\'abord).');
  }

  protected nettoyer(): void {
    this.svc.modifierImmeuble('Retrait des liaisons obsolètes', (cfg) => nettoyerLiaisons(cfg, this.listeParois()));
  }

  protected ouvrir(id: string): void {
    this.selLogement.set(id);
    this.mode.set('logement');
    this.vue.set('liaisons');
  }

  protected voirParoi(p: Paroi): void {
    const m = this.svc.model();
    if (m && p.uid) this.nav.goObject(m, p.uid);
  }
}
