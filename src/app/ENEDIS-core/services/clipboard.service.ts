import { Injectable } from '@angular/core';
import { ToasterService, TypeToast } from './toastr.service';

@Injectable({
  providedIn: 'root'
})
export class ClipboardService {

  constructor(private toasterService: ToasterService) { }

  onClipBoard(element: any, message = 'L\'élément a été copié dans le presse-papier') {

    if (!element)
      return;

    let listener = (e: ClipboardEvent) => {
      let clipboard = e.clipboardData; // || window['clipboardData'];
      if (clipboard) clipboard.setData("text", element.toString());
      e.preventDefault();
      this.toasterService.showToast(TypeToast.info, 'Copié', message);
    };

    document.addEventListener("copy", listener, false);
    document.execCommand("copy");
    document.removeEventListener("copy", listener, false);
  }
}
