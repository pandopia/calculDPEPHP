import { Component, input, output } from '@angular/core';
import { FloorplanProject, PlanEditorComponent, ProjectStats } from 'g-plan';

/**
 * Éditeur de plan g-plan. Seul composant à importer la librairie : il n'est
 * rendu que dans un bloc `@defer`, ce qui la garde hors du bundle initial.
 */
@Component({
  selector: 'app-plan-editeur',
  imports: [PlanEditorComponent],
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
