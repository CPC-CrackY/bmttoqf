import { Injectable } from '@angular/core';
import { Validators, FormBuilder, FormArray, FormControl, ReactiveFormsModule } from '@angular/forms';
import { QuestionBase, QuestionArray, QuestionGroupe } from '../models/all';

@Injectable({
  providedIn: 'root'
})
export class QuestionControlService {
  
  constructor(private fb: FormBuilder) { }
  
  toFormControl(question: any) {
    return question.required ? new FormControl(question.value || '', [Validators.required, ...question.validators])
    : new FormControl(question.value || '', question.validators || null);
  }
  toFormGroupLauncher(questions: QuestionBase<string>[] | null) {
    let group: any = {};
    if (questions) questions.forEach(question => {
      if(question.controlType === 'groupe') {
        group[question.key] = this.toFormGroupLauncher(question.questions);
      } else if(question.controlType === 'array') {
        group[question.key] = this.fb.array(question.questions.map((q) => q.controlType === 'groupe' ? this.toFormGroupLauncher(q.questions) : this.toFormControl(q)));
      } else{
        group[question.key] = this.toFormControl(question)
      }
    });
    return this.fb.group(group);
  }
  toFormGroup(questions: QuestionBase<string>[], data: any = undefined ) {
    let group: any = {};
    questions.forEach(question => {
      if(question.controlType === 'groupe') {
        group[question.key] = this.toFormGroup(question.childs);
      } else if(question.controlType === 'array') {
        group[question.key] = this.fb.array(question.childs.map((q) => q.controlType === 'groupe' ? this.toFormGroup(q.childs) : this.toFormControl(q)));
      } else{
        group[question.key] = this.toFormControl(question)
      }
    });
    return this.fb.group(group);
  }
  
  completer(questions: any, data: any = undefined, id = -1 ) {
    if(questions instanceof QuestionArray || questions.controlType === 'array') {
      questions.questions = [].concat(...(data as Array<any>).map((ligne) => {
        return (questions.childs).map((q: any) => Object.assign({}, q)).map((q: any) => {
          q.questions = this.completer(q, ligne);
          return q;
        })
      }));
    } else if(questions instanceof QuestionGroupe || questions.controlType === 'groupe') {
      return questions.questions.map((q: any,i: any) => {
        return this.completer(Object.assign({},q), data[q.key]);
      })
    } else if (questions instanceof Array) {
      return (questions).map((q) => {
        if(typeof data[q.key] !== 'undefined') {
          if(q.controlType === 'groupe') {
            q.questions = Object.values(data[q.key]).map((ligne, i) =>{
              return this.completer(Object.assign({}, q.childs[i]), ligne);
            });
            return q;
          } else if(q.controlType === 'array') {
            q.questions = [].concat(...(data[q.key] as Array<any>).map((ligne) => {
              return q.childs.map((c: any) => Object.assign({}, c)).map((c: any) => {
                c.value = ligne;
                return c;
              });
            }));
          } else {
            q.value = data[q.key];
          }
        }
        return q;
      })
    } else {
      questions.value = data;
    }
    return questions;
  }
}