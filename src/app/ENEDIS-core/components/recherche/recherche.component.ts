import { Component, OnInit, EventEmitter, Output, Input, ViewChild, ElementRef, NgZone, HostListener } from '@angular/core';

@Component({
  selector: 'app-recherche',
  templateUrl: './recherche.component.html',
  styleUrls: ['./recherche.component.scss']
})
export class RechercheComponent implements OnInit {
  @HostListener('document:keydown', ['$event'])
  onKeydownHandler(event: KeyboardEvent) {
    if (event.key == "Escape" && this.input.nativeElement['value'] !== '') {
      this.supprimer();
    }
  }
  @ViewChild('input') input!: ElementRef<HTMLFormElement>;

  @Output() rechercher = new EventEmitter<string>();

  @Input() placeholder = 'Rechercher';

  constructor() { }

  ngOnInit() {
  }

  recherche(event: any) {
    this.rechercher.emit(event.target.value.toLowerCase());
  }

  supprimer() {
    this.input.nativeElement['value'] = '';
    let event = new KeyboardEvent('keyup', { bubbles: true });
    this.input.nativeElement.dispatchEvent(event);
  }
}
