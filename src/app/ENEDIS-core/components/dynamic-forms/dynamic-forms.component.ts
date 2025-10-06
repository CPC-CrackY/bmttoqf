import { Component, OnInit, Input, Output, EventEmitter } from '@angular/core';
import { FormGroup, Validators } from '@angular/forms';
import { QuestionControlService } from './services/question-control.service';
import { FormBuilder } from '@angular/forms';
import { QuestionBase, DynamicFormulaire } from './models/all';

@Component({
  selector: 'dynamic-forms',
  templateUrl: './dynamic-forms.component.html',
  styleUrls: ['./dynamic-forms.component.css']
})
export class DynamicFormsComponent implements OnInit {

  @Input() questions: QuestionBase<string>[] | null = [];
  @Input() formulaire!: DynamicFormulaire | null;
  @Input() data: object = {};
  @Output() reponse: EventEmitter<any> = new EventEmitter();
  @Output() cancel: EventEmitter<any> = new EventEmitter();
  form!: FormGroup;
  payLoad = '';

  constructor(private qcs: QuestionControlService, private fb: FormBuilder) {  }

  ngOnInit() {
    if(this.formulaire) {
      this.form = this.qcs.toFormGroupLauncher(this.formulaire.questions);
      this.questions = this.formulaire.questions
    } else {
      this.formulaire = new DynamicFormulaire(this.questions)
      this.form = this.qcs.toFormGroupLauncher(this.formulaire.questions);
      
    }
  }

  onSubmit() {
    return this.reponse.emit(this.form.getRawValue());
  }

  onCancel($event: any) {
    this.cancel.emit($event);
  }
}
