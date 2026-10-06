import { Component, computed, inject, signal } from '@angular/core';
import { DomSanitizer, SafeResourceUrl } from '@angular/platform-browser';
import { DossierService } from '../services/dossier.service';
import { NavService } from '../services/nav.service';
import { textOf } from '../core/edition/doc-ops';
import { resolvePath } from '../core/xml/safe-xml';
import { adresseTexte, googleMapsEmbed, googleMapsEmbedAdresse, googleMapsLien, googleStreetViewEmbed, googleStreetViewLien, localisation } from '../core/localisation/localisation';
import { PlanEditeurComponent } from './plan-editeur';
import { AdresseBanComponent } from './adresse-ban';
import { compacterPlan, encodeurJpeg } from '../core/localisation/plan-compact';

const CARTES_KEY = 'calculdpe-editeur:cartes-acceptees';

/** Charge une seule fois un script ou une feuille de style servis avec l'application. */
const chargements = new Map<string, Promise<void>>();
function charger(url: string, type: 'script' | 'style'): Promise<void> {
  let p = chargements.get(url);
  if (!p) {
    p = new Promise<void>((resolve, reject) => {
      const el = type === 'script' ? Object.assign(document.createElement('script'), { src: url }) : Object.assign(document.createElement('link'), { rel: 'stylesheet', href: url });
      el.onload = () => resolve();
      el.onerror = () => { chargements.delete(url); reject(new Error(`${url} introuvable`)); };
      document.head.appendChild(el);
    });
    chargements.set(url, p);
  }
  return p;
}

/**
 * Plan et localisation du bien : carte Google Maps à partir du géocodage BAN
 * du XML, et dessin du plan avec g-plan (fond satellite centré sur le bien).
 * Le plan est conservé dans le brouillon local, jamais dans le XML ADEME.
 */
@Component({
  selector: 'app-plan',
  imports: [PlanEditeurComponent, AdresseBanComponent],
  template: `
    @let l = loc();
    <div class="plan-page">
      <section class="card">
        <div class="card-head">
          <h2>Localisation <span class="muted small">géocodage BAN du XML</span></h2>
          <span class="calc-actions">
            @if (cartes() && (l || adresse())) {
              <span class="filters">
                <button class="chip-btn" [class.active]="vue() === 'plan'" (click)="vue.set('plan')">Plan</button>
                <button class="chip-btn" [class.active]="vue() === 'satellite'" (click)="vue.set('satellite')">Satellite</button>
                @if (l) { <button class="chip-btn" [class.active]="vue() === 'streetview'" (click)="vue.set('streetview')" title="Voir le bien depuis la rue">Street View</button> }
              </span>
            }
            @if (l || adresse()) { <a class="button-link" [href]="lienGoogle()" target="_blank" rel="noopener">Ouvrir dans Google Maps ↗</a> }
            @if (l) { <a class="button-link" [href]="lienStreetView()" target="_blank" rel="noopener" title="Street View en plein écran, dans un nouvel onglet">Street View ↗</a> }
            @if (editable() && (l || adresse())) { <button class="small" (click)="recherche.set(!recherche())">{{ recherche() ? 'Annuler' : 'Changer l’adresse' }}</button> }
          </span>
        </div>
        @if (adresse()) { <p class="adresse">{{ adresse() }}</p> }
        @if (l) { <p class="muted small">{{ l.lat.toFixed(6) }}, {{ l.lng.toFixed(6) }} (WGS84, converti du Lambert 93)</p> }
        @else if (adresse()) { <p class="warn-text small">Adresse non géolocalisée : choisissez-la dans la Base Adresse Nationale pour obtenir ses coordonnées.</p> }
        @if (editable() && (recherche() || !l)) {
          <app-adresse-ban (choisie)="svc.definirAdresse($event); recherche.set(false)" />
        }
        @if (!l && !adresse()) {
          <p class="muted small">Adresse non renseignée : recherchez-la ci-dessus, ou <a href="" (click)="$event.preventDefault(); nav.go('general', 'adresse_bien')">saisissez-la dans la fiche</a>.</p>
        } @else if (!cartes()) {
          <div class="info-box small carte-accord">
            <p>Afficher la carte envoie la position du bien à Google (carte) et aux serveurs de tuiles (fond satellite du plan).</p>
            <label class="check"><input type="checkbox" #mem checked /> Ne plus demander sur ce poste</label>
            <button class="primary small" (click)="accepterCartes(mem.checked)">Afficher les cartes</button>
          </div>
        } @else if (carteUrl(); as url) {
          <iframe class="carte" [src]="url" [title]="vue() === 'streetview' ? 'Google Street View devant le bien' : 'Carte Google Maps du bien'" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
          @if (vue() === 'streetview') { <p class="muted small">Panorama le plus proche du point d'adresse : faites glisser l'image pour vous tourner vers le bien.</p> }
        }
      </section>

      <section class="card plan-card">
        <div class="card-head">
          <h2>Plan <span class="muted small">conservé dans le brouillon, hors XML ADEME</span></h2>
          @if (stats(); as s) {
            <span class="small">Surface dessinée : <strong>{{ fmt(s.totalArea) }} m²</strong>
              @if (surfaceDeclaree(); as sd) { · déclarée : {{ fmt(sd) }} m² }
              · {{ s.roomCount }} pièce(s)</span>
          }
        </div>
        @switch (etat()) {
          @case ('chargement') { <div class="plan-attente"><span class="spinner" aria-hidden="true"></span> Chargement de l'éditeur de plan…</div> }
          @case ('erreur') { <p class="error-box">Éditeur de plan indisponible : {{ erreur() }}</p> }
          @case ('pret') {
            @defer {
              <app-plan-editeur class="plan-editeur" [projet]="$any(projetInitial)" [point]="pointFond()" (changement)="enregistrer($event)" (stats)="stats.set($event)" />
            } @loading (minimum 200ms) {
              <div class="plan-attente"><span class="spinner" aria-hidden="true"></span> Chargement de l'éditeur de plan…</div>
            } @error {
              <p class="error-box">Le module de plan n'a pas pu être chargé (connexion ?).</p>
            }
          }
        }
      </section>
    </div>
  `,
})
export class PlanComponent {
  protected readonly svc = inject(DossierService);
  protected readonly nav = inject(NavService);
  private readonly sanitizer = inject(DomSanitizer);
  protected readonly vue = signal<'plan' | 'satellite' | 'streetview'>('satellite');
  protected readonly recherche = signal(false);
  protected readonly editable = computed(() => this.svc.dossier()?.format.niveauSupport === 'complet');
  protected readonly cartes = signal(lireAccord());
  protected readonly etat = signal<'chargement' | 'pret' | 'erreur'>('chargement');
  protected readonly erreur = signal('');
  protected readonly stats = signal<{ totalArea: number; roomCount: number } | null>(null);
  /** projet chargé une fois : l'éditeur le fait ensuite évoluer lui-même */
  protected readonly projetInitial = this.svc.dossier()?.plan ?? null;

  protected readonly loc = computed(() => {
    this.svc.revision();
    const d = this.svc.dossier();
    return d ? localisation(d.working) : null;
  });
  protected readonly adresse = computed(() => {
    this.svc.revision();
    const d = this.svc.dossier();
    return this.loc()?.adresse ?? (d ? adresseTexte(d.working) : null);
  });
  protected readonly surfaceDeclaree = computed(() => {
    this.svc.revision();
    const cg = resolvePath(this.svc.dossier()?.working.documentElement ?? null, 'logement/caracteristique_generale');
    const v = Number(textOf(cg, 'surface_habitable_logement') ?? textOf(cg, 'surface_habitable_immeuble'));
    return Number.isFinite(v) && v > 0 ? v : null;
  });
  protected readonly carteUrl = computed<SafeResourceUrl | null>(() => {
    const l = this.loc();
    const a = this.adresse();
    const v = this.vue();
    const url = l
      ? (v === 'streetview' ? googleStreetViewEmbed(l) : googleMapsEmbed(l, v === 'satellite'))
      : a ? googleMapsEmbedAdresse(a, v !== 'plan') : null;
    return url ? this.sanitizer.bypassSecurityTrustResourceUrl(url) : null;
  });
  protected readonly lienGoogle = computed(() => googleMapsLien(this.loc(), this.adresse()));
  protected readonly lienStreetView = computed(() => {
    const l = this.loc();
    return l ? googleStreetViewLien(l) : '';
  });
  /** fond satellite du plan centré sur le bien, seulement après accord */
  protected readonly pointFond = computed(() => {
    const l = this.loc();
    return this.cartes() && l ? { lat: l.lat, lng: l.lng, zoom: 19 } : null;
  });

  constructor() {
    // g-plan garde son propre brouillon, unique pour tous les dossiers
    // (localStorage « gplan-autosave »), et propose de le restaurer : il
    // pourrait s'agir du plan d'un autre dossier. Le plan de chaque dossier
    // est déjà dans son brouillon : on écarte celui de la librairie.
    try { localStorage.removeItem('gplan-autosave'); } catch { /* stockage indisponible */ }
    // Fabric.js (global window.fabric) et les styles MapLibre exigés par g-plan,
    // servis avec l'application et chargés à la première ouverture du plan
    Promise.all([charger('vendor/fabric.min.js', 'script'), charger('vendor/maplibre-gl.css', 'style')])
      .then(() => this.etat.set('pret'))
      .catch((e: Error) => { this.erreur.set(e.message); this.etat.set('erreur'); });
  }

  /** images du plan déjà ré-encodées (le fond ne change pas d'une modification à l'autre) */
  private readonly images = new Map<string, string>();
  private sauvegarde = 0;

  protected async enregistrer(p: Record<string, unknown>): Promise<void> {
    const n = ++this.sauvegarde;
    const compact = await compacterPlan(p, encodeurJpeg, this.images);
    if (n === this.sauvegarde) this.svc.enregistrerPlan(compact);
  }

  protected accepterCartes(memoriser: boolean): void {
    this.cartes.set(true);
    if (memoriser) {
      try { localStorage.setItem(CARTES_KEY, '1'); } catch { /* commodité seulement */ }
    }
  }

  protected fmt(n: number): string {
    return n.toLocaleString('fr-FR', { maximumFractionDigits: 1 });
  }
}

function lireAccord(): boolean {
  try {
    return localStorage.getItem(CARTES_KEY) === '1';
  } catch {
    return false;
  }
}
