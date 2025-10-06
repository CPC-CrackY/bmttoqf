import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Nl2brPipe } from './nl2br.pipe';
import { SafePipe } from './safe.pipe';
import { UnescapePipe } from './unescape.pipe';

@NgModule({
  declarations: [Nl2brPipe, SafePipe, UnescapePipe],
  imports: [
    CommonModule
  ],
  exports: [
    Nl2brPipe, SafePipe, UnescapePipe
  ]
})
export class CorePipesModule { }
