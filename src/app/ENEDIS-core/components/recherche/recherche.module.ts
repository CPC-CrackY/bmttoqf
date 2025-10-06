import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RechercheComponent } from './recherche.component';

@NgModule({
  declarations: [RechercheComponent],
  imports: [
    CommonModule,
  ], exports: [
    RechercheComponent,
  ]
})
export class RechercheModule { }
