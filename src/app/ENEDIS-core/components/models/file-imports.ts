export class FileImport  {
    id: number;
    table: string;
    lastImportDate: string;

    constructor(public obj: any) {
        this.id = obj.id;
        this.table = obj.table;
        this.lastImportDate = obj.lastImportDate;
    }

}
