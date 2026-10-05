import { Component, computed, inject } from '@angular/core';
import { DossierService } from '../services/dossier.service';
import { NavService } from '../services/nav.service';
import { textOf } from '../core/edition/doc-ops';
import { resolvePath, childElements } from '../core/xml/safe-xml';
import { BarItem, BarsComponent, EtiquetteComponent } from './charts';

const num = (v: string | null) => (v === null || Number.isNaN(Number(v)) ? 0 : Number(v));
const fmt = (n: number, d = 0) => n.toLocaleString('fr-FR', { maximumFractionDigits: d });

/** Vue d'ensemble des résultats du fichier source : étiquettes, usages, déperditions, énergies. */
@Component({
  selector: 'app-bilan',
  imports: [BarsComponent, EtiquetteComponent],
  template: `
    @let d = svc.dossier()!;
    @let b = bilan();
    <div class="bilan">
      @if (!b) {
        <div class="card"><h2>Bilan</h2><p class="muted">Aucun résultat dans le fichier : ils doivent être produits par un moteur de calcul (aucun n'est connecté).</p></div>
      } @else {
        <p class="info-box small">
          Résultats écrits par le logiciel d'origine, <strong>non recalculés</strong> par cet outil.
          @if (d.meta.resultatsObsoletes) { <strong class="warn-text">Des données d'entrée ont changé : ils sont à recalculer.</strong> }
        </p>
        <div class="bilan-grid">
          <section class="card">
            <h3>Étiquette énergie</h3>
            <app-etiquette [classe]="b.classeEnergie" [value]="b.ep + ' kWh/m²/an'" [stale]="d.meta.resultatsObsoletes" />
          </section>
          <section class="card">
            <h3>Étiquette climat</h3>
            <app-etiquette type="climat" [classe]="b.classeClimat" [value]="b.ges + ' kg CO₂/m²/an'" [stale]="d.meta.resultatsObsoletes" />
          </section>
          <section class="card kpis">
            <div class="kpi"><span class="kpi-v">{{ b.cout }} €</span><span class="kpi-l">coût annuel estimé (5 usages)</span></div>
            <div class="kpi"><span class="kpi-v">{{ b.epTotal }}</span><span class="kpi-l">kWh/an d'énergie primaire</span></div>
            <div class="kpi"><span class="kpi-v">{{ b.efTotal }}</span><span class="kpi-l">kWh/an d'énergie finale</span></div>
            <div class="kpi"><span class="kpi-v">{{ b.gesTotal }}</span><span class="kpi-l">kg CO₂/an</span></div>
          </section>
          <section class="card">
            <h3>Consommation par usage <span class="muted small">énergie primaire</span></h3>
            <app-bars [items]="b.usages" unit="kWh/an" (pick)="go($event)" />
          </section>
          <section class="card">
            <h3>Où part la chaleur <span class="muted small">déperditions</span></h3>
            <app-bars [items]="b.deperditions" unit="W/K" color="#d0702a" (pick)="go($event)" />
          </section>
          <section class="card">
            <h3>Par énergie</h3>
            <table class="table compact">
              <thead><tr><th>Énergie</th><th class="num">kWh/an</th><th class="num">kg CO₂/an</th><th class="num">€/an</th></tr></thead>
              <tbody>
                @for (e of b.energies; track e.label) {
                  <tr><td>{{ e.label }}</td><td class="num">{{ e.conso }}</td><td class="num">{{ e.ges }}</td><td class="num">{{ e.cout }}</td></tr>
                }
              </tbody>
            </table>
          </section>
        </div>
        <p class="muted small">Le détail de chaque bloc (y compris les variantes « dépensier ») est dans les sections de gauche.</p>
      }
    </div>
  `,
})
export class BilanComponent {
  protected readonly svc = inject(DossierService);
  private readonly nav = inject(NavService);

  protected readonly bilan = computed(() => {
    this.svc.revision();
    const d = this.svc.dossier();
    const sortie = d ? resolvePath(d.working.documentElement, 'logement/sortie') : null;
    if (!d || !sortie) return null;
    const v = (rel: string) => num(textOf(sortie, rel));
    const energyLabels = d.schema?.def('dpe/logement/sortie/sortie_par_energie_collection/sortie_par_energie/enum_type_energie_id')?.enumLabels ?? {};
    const usages: BarItem[] = [
      { label: 'Chauffage', value: v('ep_conso/ep_conso_ch'), key: 'installations_chauffage' },
      { label: 'Eau chaude', value: v('ep_conso/ep_conso_ecs'), key: 'installations_ecs' },
      { label: 'Refroidissement', value: v('ep_conso/ep_conso_fr'), key: 'climatisations' },
      { label: 'Éclairage', value: v('ep_conso/ep_conso_eclairage') },
      { label: 'Auxiliaires', value: v('ep_conso/ep_conso_totale_auxiliaire'), key: 'ventilations' },
    ];
    const deperditions: BarItem[] = [
      { label: 'Murs', value: v('deperdition/deperdition_mur'), key: 'murs' },
      { label: 'Renouvellement d\'air', value: v('deperdition/deperdition_renouvellement_air'), key: 'ventilations' },
      { label: 'Ponts thermiques', value: v('deperdition/deperdition_pont_thermique'), key: 'ponts_thermiques' },
      { label: 'Baies vitrées', value: v('deperdition/deperdition_baie_vitree'), key: 'baies' },
      { label: 'Planchers hauts', value: v('deperdition/deperdition_plancher_haut'), key: 'planchers_hauts' },
      { label: 'Portes', value: v('deperdition/deperdition_porte'), key: 'portes' },
      { label: 'Planchers bas', value: v('deperdition/deperdition_plancher_bas'), key: 'planchers_bas' },
    ];
    const coll = resolvePath(sortie, 'sortie_par_energie_collection');
    const energies = (coll ? childElements(coll) : []).map((e) => {
      const code = textOf(e, 'enum_type_energie_id') ?? '';
      const l = energyLabels[code] ?? code;
      return { label: l.charAt(0).toUpperCase() + l.slice(1), conso: fmt(num(textOf(e, 'conso_5_usages'))), ges: fmt(num(textOf(e, 'emission_ges_5_usages'))), cout: fmt(num(textOf(e, 'cout_5_usages'))) };
    });
    return {
      classeEnergie: textOf(sortie, 'ep_conso/classe_bilan_dpe'),
      classeClimat: textOf(sortie, 'emission_ges/classe_emission_ges'),
      ep: fmt(v('ep_conso/ep_conso_5_usages_m2')),
      ges: fmt(v('emission_ges/emission_ges_5_usages_m2')),
      cout: fmt(v('cout/cout_5_usages')),
      epTotal: fmt(v('ep_conso/ep_conso_5_usages')),
      efTotal: fmt(v('ef_conso/conso_5_usages')),
      gesTotal: fmt(v('emission_ges/emission_ges_5_usages')),
      usages, deperditions, energies,
    };
  });

  protected go(section: string): void {
    const tab = ['murs', 'ponts_thermiques', 'baies', 'planchers_hauts', 'portes', 'planchers_bas'].includes(section) ? 'enveloppe' : 'systemes';
    this.nav.go(tab, section);
  }
}
