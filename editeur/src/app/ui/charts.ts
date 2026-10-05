import { Component, computed, input, output } from '@angular/core';

export interface BarItem {
  label: string;
  value: number;
  display?: string;
  key?: string;
}

/** Barres horizontales proportionnelles (part de chaque poste). */
@Component({
  selector: 'app-bars',
  template: `
    <ul class="bars">
      @for (b of rows(); track b.label) {
        <li [class.clickable]="!!b.key" (click)="b.key && pick.emit(b.key)">
          <span class="b-label">{{ b.label }}</span>
          <span class="b-track"><span class="b-fill" [style.width.%]="b.pct" [style.background]="color()"></span></span>
          <span class="b-value">{{ b.display ?? fmt(b.value) }}<span class="muted"> {{ unit() }}</span></span>
          @if (showShare()) { <span class="b-share muted">{{ b.share }} %</span> }
        </li>
      }
    </ul>
  `,
})
export class BarsComponent {
  readonly items = input.required<BarItem[]>();
  readonly unit = input('');
  readonly color = input('var(--accent)');
  readonly showShare = input(true);
  readonly pick = output<string>();

  protected readonly rows = computed(() => {
    const list = this.items().filter((b) => b.value > 0);
    const max = Math.max(...list.map((b) => b.value), 1);
    const total = list.reduce((n, b) => n + b.value, 0) || 1;
    return list
      .sort((a, b) => b.value - a.value)
      .map((b) => ({ ...b, pct: (b.value / max) * 100, share: Math.round((b.value / total) * 100) }));
  });

  protected fmt(n: number): string {
    return n.toLocaleString('fr-FR', { maximumFractionDigits: n < 10 ? 1 : 0 });
  }
}

/** Échelle d'étiquette A → G, classe du fichier mise en évidence (aucun seuil recalculé). */
@Component({
  selector: 'app-etiquette',
  template: `
    <div class="etiq-scale" [class.climat]="type() === 'climat'" [class.stale]="stale()">
      @for (c of classes; track c; let i = $index) {
        <div class="etiq-row" [class.on]="c === classe()" [style.width.%]="40 + i * 10">
          <span class="etiq-bar" [attr.data-c]="c">{{ c }}</span>
          @if (c === classe()) { <span class="etiq-val">{{ value() }}</span> }
        </div>
      }
    </div>
  `,
})
export class EtiquetteComponent {
  readonly classe = input<string | null>(null);
  readonly value = input('');
  readonly type = input<'energie' | 'climat'>('energie');
  readonly stale = input(false);
  protected readonly classes = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
}
