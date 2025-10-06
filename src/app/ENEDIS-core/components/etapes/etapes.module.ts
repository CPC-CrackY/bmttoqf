import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { EtapesComponent } from './etapes.component';

@NgModule({
  declarations: [EtapesComponent],
  imports: [
    CommonModule
  ],
  exports: [
    EtapesComponent
  ]
})
export class EtapesModule { }
