import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { SpinnerComponent } from './spinner.component';
import { LoaderService } from '../../services/loader.service';

@NgModule({
  declarations: [SpinnerComponent],
  imports: [
    CommonModule
  ],
  exports: [
    SpinnerComponent
  ],
  providers: [LoaderService]
})
export class SpinnerModule { }
