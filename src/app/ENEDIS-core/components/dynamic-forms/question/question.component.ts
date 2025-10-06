import { Component, Input } from '@angular/core';
import { FormGroup, FormArray } from '@angular/forms';
import { QuestionControlService } from '../services/question-control.service';
import { QuestionBase, QuestionArray, QuestionGroupe } from '../models/all';

@Component({
  selector: 'app-question',
  templateUrl: './question.component.html',
  styleUrls: ['./question.component.css']
})
export class QuestionComponent {

  @Input() question: any | QuestionBase<string> | QuestionArray | QuestionGroupe;
  @Input() form!: FormGroup;
  @Input() questionId!: number;
  @Input() parent!: QuestionBase<string>;

  constructor(private qcs: QuestionControlService) {
  }

  getFormGroup(key: any) {
    if (typeof this.parent !== "undefined" && this.parent.controlType === 'array') {
      return this.form.controls[key] as FormGroup;
    } else {
      return this.form.get(key) as FormGroup;
    }

  }

  get key(): string {
    if (typeof this.form.controls[this.question.key] === "undefined") {
      return this.questionId.toString();
    } else {
      return this.question.key;
    }
  }

  get isValid() {
    return this.form.controls[this.key].valid;
  }

  getErrors(key: any) {
    let errors = this.form.controls[this.key].errors;
    if (errors && errors[key]) return errors[key];
  }

  addChild(key: any, questions: any, childs: any) {
    childs.map((q: any) => q).forEach((question: any) => {
      if (question.controlType === 'groupe') {
        questions.push(question);
        this.getFormArray(key).push(this.qcs.toFormGroup(question.childs.map((q: any) => q)));
      } else if (question.controlType === 'array') {
        this.getFormArray(key).push(this.qcs.toFormGroup(question.childs.map((q: any) => q)));
      } else {
        questions.push(question);
        this.getFormArray(key).push(this.qcs.toFormControl(question));
      }
    });
  }

  getFormArray(key: any): FormArray {
    return this.form.get(key) as FormArray;
  }

  removeChild(e: any, i: any) {
    let array = this.getFormArray(this.key);
    array.removeAt(i)
  }

}
