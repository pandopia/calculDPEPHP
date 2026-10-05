import { Component, computed, inject } from '@angular/core';
import { DossierService } from '../services/dossier.service';
import { NavService } from '../services/nav.service';
import { resolvePath } from '../core/xml/safe-xml';
import { displayRounded, displayValue } from '../core/edition/value-codec';
import { schemaPath } from '../core/schema/schema-registry';
import { textOf } from '../core/edition/doc-ops';
import { Issue } from '../core/validation/issues';
import { TabKey } from '../core/metier/catalog';
import { BarItem, BarsComponent, EtiquetteComponent } from './charts';
import { Maquette3dComponent } from './maquette-3d';

const num = (v: string | null) => (v === null || Number.isNaN(Number(v)) ? 0 : Number(v));

interface Todo {
  tab: TabKey;
  section: string;
  label: string;
  errors: number;
  missing: number;
  first: Issue | null;
}

@Component({
  selector: 'app-synthese',
  imports: [BarsComponent, EtiquetteComponent, Maquette3dComponent],
  template: `
    @let d = svc.dossier()!;
    <div class="synthese">
      <section class="facts">
        @for (f of facts(); track f.label) {
          <div class="fact" [class.missing]="!f.value" (click)="nav.go(f.tab, f.section)">
            <span class="fact-v">{{ f.value || '—' }}@if (f.value && f.unit) { <small> {{ f.unit }}</small> }</span>
            <span class="fact-l">{{ f.label }}</span>
          </div>
        }
      </section>

      <div class="dash">
        <section class="card perf">
          <h2>Performance <span class="muted small">résultats du fichier source</span></h2>
          @if (perf(); as p) {
            <div class="perf-scales">
              <div><h4>Énergie</h4><app-etiquette [classe]="p.e" [value]="p.ep" [stale]="d.meta.resultatsObsoletes" /></div>
              <div><h4>Climat</h4><app-etiquette type="climat" [classe]="p.c" [value]="p.ges" [stale]="d.meta.resultatsObsoletes" /></div>
            </div>
            <p class="small">Coût estimé : <strong>{{ p.cout }} €/an</strong>
              @if (d.meta.resultatsObsoletes) { · <span class="warn-text">à recalculer</span> }
              · <a href="" (click)="$event.preventDefault(); nav.go('resultats', 'bilan')">voir le bilan</a></p>
          } @else {
            <p class="muted">Aucun résultat dans le fichier (aucun moteur de calcul connecté).</p>
          }
        </section>

        <section class="card">
          <h2>Enveloppe <span class="muted small">surfaces déclarées</span></h2>
          @if (surfaces().length) {
            <app-bars [items]="surfaces()" unit="m²" color="#4a7fc1" (pick)="nav.go('enveloppe', $event)" />
          } @else { <p class="muted">Aucune paroi déclarée. <a href="" (click)="$event.preventDefault(); nav.go('enveloppe', 'murs')">Ajouter un mur</a></p> }
          @if (orientations().length) {
            <h4>Vitrages par orientation</h4>
            <div class="compass">
              @for (o of orientations(); track o.label) { <div class="compass-cell"><strong>{{ o.label }}</strong><span>{{ o.display }} m²</span></div> }
            </div>
          }
        </section>

        <section class="card">
          <h2>Équipements</h2>
          <ul class="equip">
            @for (e of equipements(); track e.label) {
              <li (click)="nav.go('systemes', e.section, e.uid)" [class.empty]="!e.uid">
                <span class="eq-icon">{{ e.icon }}</span>
                <span class="eq-body"><strong>{{ e.label }}</strong><span class="muted small clamp2">{{ e.detail }}</span></span>
              </li>
            }
          </ul>
        </section>

        <section class="card todo">
          <h2>À faire @if (todos().length) { <span class="dot miss">{{ todoCount() }}</span> }</h2>
          @if (todos().length === 0) {
            <p class="ok-text">✓ Rien de bloquant détecté par les contrôles en direct.</p>
          } @else {
            <ul class="todo-list">
              @for (t of todos(); track t.label) {
                <li (click)="openTodo(t)">
                  <span class="todo-sec">{{ t.label }}</span>
                  <span class="todo-msg muted small">{{ t.first?.message }}</span>
                  <span class="todo-n">@if (t.errors) { <span class="dot err">{{ t.errors }}</span> } @if (t.missing) { <span class="dot miss">{{ t.missing }}</span> }</span>
                </li>
              }
            </ul>
          }
          <p class="muted small">Validation XSD complète et export : <a href="" (click)="$event.preventDefault(); nav.go('controles')">Vérifier et exporter</a></p>
        </section>
      </div>

      <app-maquette-3d class="maquette-bottom" />

      <p class="format-line muted small">
        {{ d.format.libelleFamille }} · modèle {{ d.format.enumVersionId }} ({{ d.format.xsd ?? 'sans schéma' }})
        · {{ d.meta.origine === 'import' ? 'importé de ' + d.meta.nomFichier : 'nouveau dossier' }}
        @if (d.format.numeroDpe) { · n° ADEME {{ d.format.numeroDpe }} }
      </p>
    </div>
  `,
})
export class SyntheseComponent {
  protected readonly svc = inject(DossierService);
  protected readonly nav = inject(NavService);

  private root(): Element {
    this.svc.revision();
    return this.svc.dossier()!.working.documentElement;
  }

  private val(rel: string, round = false): string {
    const root = this.root();
    const raw = textOf(root, rel);
    if (raw === null) return '';
    const leaf = resolvePath(root, rel);
    const def = leaf ? this.svc.dossier()!.schema?.def(schemaPath(leaf)) ?? null : null;
    return round ? displayRounded(raw, 1) : displayValue(raw, def);
  }

  protected readonly facts = computed(() => {
    const cg = 'logement/caracteristique_generale';
    const immeuble = !!textOf(this.root(), `${cg}/surface_habitable_immeuble`) && !textOf(this.root(), `${cg}/surface_habitable_logement`);
    return [
      { label: 'Année de construction', value: this.val(`${cg}/annee_construction`) || this.val(`${cg}/enum_periode_construction_id`), unit: '', tab: 'batiment' as TabKey, section: 'caracteristique_generale' },
      { label: immeuble ? 'Surface habitable (immeuble)' : 'Surface habitable', value: this.val(immeuble ? `${cg}/surface_habitable_immeuble` : `${cg}/surface_habitable_logement`, true), unit: 'm²', tab: 'batiment' as TabKey, section: 'caracteristique_generale' },
      { label: 'Hauteur sous plafond', value: this.val(`${cg}/hsp`, true), unit: 'm', tab: 'batiment' as TabKey, section: 'caracteristique_generale' },
      { label: 'Zone climatique', value: this.val('logement/meteo/enum_zone_climatique_id'), unit: '', tab: 'batiment' as TabKey, section: 'meteo' },
      { label: 'Altitude', value: this.val('logement/meteo/enum_classe_altitude_id'), unit: '', tab: 'batiment' as TabKey, section: 'meteo' },
      { label: 'Inertie', value: this.val('logement/enveloppe/inertie/enum_classe_inertie_id'), unit: '', tab: 'enveloppe' as TabKey, section: 'inertie' },
      { label: 'Établi le', value: this.val('administratif/date_etablissement_dpe'), unit: '', tab: 'general' as TabKey, section: 'administratif' },
    ];
  });

  protected readonly perf = computed(() => {
    const s = resolvePath(this.root(), 'logement/sortie');
    if (!s) return null;
    return {
      e: textOf(s, 'ep_conso/classe_bilan_dpe'),
      c: textOf(s, 'emission_ges/classe_emission_ges'),
      ep: `${displayRounded(textOf(s, 'ep_conso/ep_conso_5_usages_m2') ?? '', 0)} kWh/m²/an`,
      ges: `${displayRounded(textOf(s, 'emission_ges/emission_ges_5_usages_m2') ?? '', 0)} kg CO₂/m²/an`,
      cout: displayRounded(textOf(s, 'cout/cout_5_usages') ?? '', 0),
    };
  });

  private objectsOf(kind: string) {
    const m = this.svc.model();
    return m ? [...m.objects.values()].filter((o) => o.kind?.key === kind) : [];
  }

  private sum(kind: string, rel: string, mult?: string): number {
    const index = this.svc.dossier()!.index();
    return this.objectsOf(kind).reduce((n, o) => {
      const el = index.get(o.uid)!;
      return n + num(textOf(el, rel)) * (mult ? Math.max(1, num(textOf(el, mult))) : 1);
    }, 0);
  }

  protected readonly surfaces = computed<BarItem[]>(() => [
    { label: `Murs (${this.objectsOf('mur').length})`, value: this.sum('mur', 'donnee_entree/surface_paroi_opaque'), key: 'murs' },
    { label: `Planchers bas (${this.objectsOf('plancher_bas').length})`, value: this.sum('plancher_bas', 'donnee_entree/surface_paroi_opaque'), key: 'planchers_bas' },
    { label: `Planchers hauts (${this.objectsOf('plancher_haut').length})`, value: this.sum('plancher_haut', 'donnee_entree/surface_paroi_opaque'), key: 'planchers_hauts' },
    { label: `Baies vitrées (${this.objectsOf('baie_vitree').length})`, value: this.sum('baie_vitree', 'donnee_entree/surface_totale_baie'), key: 'baies' },
    { label: `Portes (${this.objectsOf('porte').length})`, value: this.sum('porte', 'donnee_entree/surface_porte'), key: 'portes' },
  ].filter((b) => b.value > 0));

  protected readonly orientations = computed(() => {
    const d = this.svc.dossier()!;
    const labels = d.schema?.def('dpe/logement/enveloppe/baie_vitree_collection/baie_vitree/donnee_entree/enum_orientation_id')?.enumLabels ?? {};
    const by = new Map<string, number>();
    for (const o of this.objectsOf('baie_vitree')) {
      const el = d.index().get(o.uid)!;
      const code = textOf(el, 'donnee_entree/enum_orientation_id');
      if (!code) continue;
      const l = labels[code] ?? code;
      by.set(l, (by.get(l) ?? 0) + num(textOf(el, 'donnee_entree/surface_totale_baie')));
    }
    return [...by].map(([label, v]) => ({ label: label.charAt(0).toUpperCase() + label.slice(1), display: displayRounded(String(v), 1) }));
  });

  protected readonly equipements = computed(() => {
    const line = (kind: string, section: string, icon: string, label: string) => {
      const objs = this.objectsOf(kind);
      if (!objs.length) return [{ label, detail: 'Non déclaré', icon, section, uid: null as string | null }];
      return objs.map((o) => ({ label: objs.length > 1 ? `${label} — ${o.title.slice(0, 40)}` : label, detail: o.name || o.summary.map((s) => s.value).join(' · '), icon, section, uid: o.uid }));
    };
    return [
      ...line('installation_chauffage', 'installations_chauffage', '🔥', 'Chauffage'),
      ...line('installation_ecs', 'installations_ecs', '🚿', 'Eau chaude'),
      ...line('ventilation', 'ventilations', '🌬', 'Ventilation'),
      ...this.objectsOf('climatisation').length ? line('climatisation', 'climatisations', '❄', 'Climatisation') : [],
    ];
  });

  protected readonly todos = computed<Todo[]>(() => {
    const m = this.svc.model();
    if (!m) return [];
    const out: Todo[] = [];
    for (const t of m.tabs) {
      for (const s of t.sections) {
        if (!s.errors && !s.missing) continue;
        const uids = new Set([...(s.uids ?? []), ...(s.singletonUid ? [s.singletonUid] : [])]);
        for (const u of s.uids) for (const c of m.objects.get(u)?.childUids ?? []) uids.add(c);
        const first = m.issues.find((i) => i.gravite !== 'info' && ((i.uid && uids.has(i.uid)) || i.nav?.section === s.def.key)) ?? null;
        out.push({ tab: t.def.key, section: s.def.key, label: `${t.def.label} › ${s.def.label}`, errors: s.errors, missing: s.missing, first });
      }
    }
    return out.sort((a, b) => b.errors - a.errors);
  });
  protected readonly todoCount = computed(() => this.todos().reduce((n, t) => n + t.errors + t.missing, 0));

  protected openTodo(t: Todo): void {
    const m = this.svc.model()!;
    if (t.first) this.nav.goIssue(m, t.first);
    else this.nav.go(t.tab, t.section);
  }
}
