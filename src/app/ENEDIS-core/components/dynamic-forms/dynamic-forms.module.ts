import { NgModule, CUSTOM_ELEMENTS_SCHEMA } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';


import { DynamicFormsComponent } from './dynamic-forms.component';
import { QuestionComponent } from './question/question.component';


@NgModule({
  declarations: [DynamicFormsComponent, QuestionComponent],
  imports: [
    CommonModule,
    FormsModule,
    ReactiveFormsModule
  ],
  exports: [
    DynamicFormsComponent,
    QuestionComponent,
  ]
  // schemas: [CUSTOM_ELEMENTS_SCHEMA]
})
export class DynamicFormsModule { }
