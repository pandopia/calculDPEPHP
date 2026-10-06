import { Component, input, output } from '@angular/core';
import { FloorplanProject, PlanEditorComponent, ProjectStats, provideGPlan } from 'g-plan';

/**
 * Éditeur de plan g-plan. Seul composant à importer la librairie : il n'est
 * rendu que dans un bloc `@defer`, ce qui la garde hors du bundle initial.
 */
@Component({
  selector: 'app-plan-editeur',
  imports: [PlanEditorComponent],
  // maplibre-gl 6 : son worker (décodage des fonds vectoriels) est servi avec
  // l'application (angular.json → assets → vendor/maplibre-gl-{worker,shared}.mjs).
  // URL relative : résolue sous le base-href (/calculDPEPHP/ sur GitHub Pages).
  providers: [provideGPlan({ maplibreWorkerUrl: 'vendor/maplibre-gl-worker.mjs' })],
  template: `
    <ng-gplan-plan-editor
      [project]="projet()"
      [geoPoint]="point()"
      autoGeoLayer="satellite"
      (projectChange)="changement.emit($any($event))"
      (statsChanged)="stats.emit($event)" />
  `,
})
export class PlanEditeurComponent {
  /** projet chargé à l'ouverture (jamais réinjecté ensuite : l'éditeur garde la main) */
  readonly projet = input<FloorplanProject | null>(null);
  readonly point = input<{ lat: number; lng: number; zoom?: number } | null>(null);
  readonly changement = output<Record<string, unknown>>();
  readonly stats = output<ProjectStats>();
}
