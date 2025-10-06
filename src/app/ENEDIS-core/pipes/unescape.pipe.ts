import { Pipe, PipeTransform } from '@angular/core';

@Pipe({
  name: 'unescape'
})
export class UnescapePipe implements PipeTransform {

  transform(value: any): string|null {
    if (!value) return null;
    let str = value;
    str = str.replaceAll(/&lt;/g , "<");	 
    str = str.replaceAll(/&gt;/g , ">");     
    str = str.replaceAll(/&quot;/g , "\"");  
    str = str.replaceAll(/&#39;/g , "\'");   
    str = str.replaceAll(/&amp;/g , "&");
    return str;
  }

}
