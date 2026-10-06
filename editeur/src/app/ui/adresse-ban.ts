import { Component, input, output, signal } from '@angular/core';
import { AdresseBan, rechercherAdresses } from '../core/localisation/geocodage';

/**
 * Recherche d'adresse par la Base Adresse Nationale (saisie semi-automatique).
 * Les appels partent à la frappe, après 3 caractères et une courte pause.
 */
@Component({
  selector: 'app-adresse-ban',
  template: `
    <div class="adresse-ban">
      <input type="search" [placeholder]="placeholder()" [value]="texte()" (input)="saisir($any($event.target).value)"
        (keydown.arrowdown)="$event.preventDefault(); bouger(1)" (keydown.arrowup)="$event.preventDefault(); bouger(-1)"
        (keydown.enter)="$event.preventDefault(); valider()" (keydown.escape)="resultats.set([])" (blur)="fermerPlusTard()"
        role="combobox" aria-autocomplete="list" [attr.aria-expanded]="resultats().length > 0" aria-label="Adresse du bien" />
      @if (enCours()) { <span class="spinner small-spinner ab-spin" aria-hidden="true"></span> }
      @if (resultats().length) {
        <ul class="ab-liste" role="listbox">
          @for (r of resultats(); track r.id; let i = $index) {
            <li role="option" [attr.aria-selected]="i === actif()" [class.actif]="i === actif()" (mousedown)="$event.preventDefault(); choisir(r)">
              <strong>{{ r.label }}</strong> <span class="muted small">{{ r.context }}</span>
            </li>
          }
        </ul>
      }
      @if (erreur()) { <p class="err-text small">{{ erreur() }}</p> }
      <p class="muted small ab-source">Recherche dans la Base Adresse Nationale (api-adresse.data.gouv.fr).</p>
    </div>
  `,
})
export class AdresseBanComponent {
  readonly placeholder = input('Adresse du bien, ex. 3 rue Juiverie Lyon');
  readonly choisie = output<AdresseBan>();
  protected readonly texte = signal('');
  protected readonly resultats = signal<AdresseBan[]>([]);
  protected readonly actif = signal(0);
  protected readonly enCours = signal(false);
  protected readonly erreur = signal('');
  private minuterie: ReturnType<typeof setTimeout> | null = null;
  private requete = 0;

  protected saisir(v: string): void {
    this.texte.set(v);
    if (this.minuterie) clearTimeout(this.minuterie);
    this.minuterie = setTimeout(() => void this.chercher(v), 250);
  }

  private async chercher(v: string): Promise<void> {
    const n = ++this.requete;
    this.erreur.set('');
    if (v.trim().length < 3) { this.resultats.set([]); return; }
    this.enCours.set(true);
    try {
      const r = await rechercherAdresses(v);
      if (n === this.requete) { this.resultats.set(r); this.actif.set(0); }
    } catch (e) {
      if (n === this.requete) this.erreur.set(e instanceof Error ? e.message : String(e));
    } finally {
      if (n === this.requete) this.enCours.set(false);
    }
  }

  protected bouger(d: number): void {
    const n = this.resultats().length;
    if (n) this.actif.set((this.actif() + d + n) % n);
  }

  protected valider(): void {
    const r = this.resultats()[this.actif()];
    if (r) this.choisir(r);
  }

  protected choisir(r: AdresseBan): void {
    this.texte.set(r.label);
    this.resultats.set([]);
    this.choisie.emit(r);
  }

  protected fermerPlusTard(): void {
    setTimeout(() => this.resultats.set([]), 150);
  }
}
