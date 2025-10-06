export declare interface Options<T = string>{
    value?: T;
    placeholder?: string;
    key?: string;
    label?: string;
    labelClass?: string[] | string;
    required?: boolean;
    order?: number;
    controlType?: string;
    type?: string;
    class?: string[] | string;
    options?: {key: string, value: string}[];
    invalidClass?: string[] | string;
    validators?: ((...args: any[]) => any) [];
    messages?: {validator: string, message: string}[];
    group?: boolean;
    questions?: QuestionBase<T>[];
    childs?: QuestionBase<T>[];
    inputClass?: string[] | string;
}

export class DynamicFormulaire<T = string> {
    constructor(public questions: QuestionBase<T>[] | null,
        public buttonSubmit: {class: string[]|string, texte: string} = {class:'', texte:'Envoyer'},
        public buttonCancel?: {class: string[]|string, texte: string},
        public formClass: string[]|string = '') { }
}

export class QuestionBase<T = string> {
    value!: T;
    placeholder: string;
    key: string;
    label: string;
    labelClass?: string[] | string;
    required: boolean;
    order: number;
    controlType: string;
    type: string;
    class?: string[] | string;
    options!: {key: string, value: string}[];
    invalidClass?: string[] | string;
    validators: ((...args: any[]) => any) [];
    messages: {validator: string, message: string}[];
    group: boolean;
    questions: QuestionBase<T>[];
    childs!: QuestionBase<T>[];
    inputClass?: string[] | string;
    
    
    constructor(options: Options<T> = {}) {
        if (options.value) this.value = options.value;
        this.placeholder = options.placeholder || '';
        this.key = options.key || '';
        this.label = options.label || '';
        this.labelClass = options.labelClass || '';
        this.required = !!options.required;
        this.order = options.order === undefined ? 1 : options.order;
        this.controlType = options.controlType || '';
        this.type = options.type || '';
        this.class = options.class || '';
        this.invalidClass = options.invalidClass || '';
        this.validators = options.validators || [];
        this.messages = options.messages || [];
        this.group = options.group || false;
        this.questions = options.questions || [];
        this.inputClass = options.inputClass || '';
    }
    
}


export class QuestionArray<T = string> extends QuestionBase<T> {
    override controlType = 'array';
    override questions: QuestionBase<T>[];
    override childs: QuestionBase<T>[];
    buttonAddClass: string[] | string;
    buttonAddText: string;
    buttonRemoveClass: string[] | string;
    buttonRemoveText: string;
    fieldsetClass?: string[] | string;
    loopClass?: string[] | string;
    
    constructor(options: {
        value?: T,
        placeholder?: string,
        key?: string,
        label?: string,
        required?: boolean,
        order?: number,
        controlType?: string,
        type?: string,
        class?: string[] | string,
        invalidClass?: string[] | string,
        validators?: ((...args: any[]) => any) [],
        messages?: {validator: string, message: string}[],
        group?: boolean,
        questions?: QuestionBase<T>[],
        buttonAddClass?: string[] | string,
        buttonAddText?: string,
        buttonRemoveClass?: string[] | string,
        buttonRemoveText?: string,
        fieldsetClass?: string[] | string,
        loopClass?: string[] | string,
    } = {}) {
        super(options);
        this.questions = options['questions'] || [];
        let childs: any = null;
        if (options['questions']) childs = options['questions'].map((q) => q);
        this.childs = childs || [];
        this.buttonAddText = options['buttonAddText'] || '+' ;
        this.buttonAddClass = options['buttonAddClass'] || [];
        this.buttonRemoveClass = options['buttonRemoveClass'] || [];
        this.buttonRemoveText = options['buttonRemoveText'] || '-' ;
        this.fieldsetClass = options['fieldsetClass'] || '';
        this.loopClass = options['loopClass'] || '';
    }
}

export class QuestionCheckbox<T = string> extends QuestionBase<T> {
    override controlType = 'checkbox';
    override type: string;
    
    constructor(options: Options<T> = {}) {
        super(options);
        this.type = options['type'] || '';
    }
    
}

export class QuestionDropdown<T = string> extends QuestionBase<T> {
    override controlType = 'dropdown';
    override options: {key: string, value: string}[] = [];
    
    constructor(options: {
        value?: T,
        placeholder?: string,
        key?: string,
        label?: string,
        labelClass?: string[] | string,
        required?: boolean,
        order?: number,
        controlType?: string,
        type?: string,
        options?: {key: string, value: string}[],
        class?: string[] | string,
        invalidClass?: string[] | string,
        validators?: ((...args: any[]) => any) [],
        messages?: {validator: string, message: string}[],
        group?: boolean,
        questions?: QuestionBase<T>[],
        inputClass?: string[] | string,
    } = {}) {
        super(options);
        this.options = options['options'] || [];
    }
}

export class QuestionGroupe<T = string> extends QuestionBase<T> {
    override controlType = 'groupe';
    override questions: QuestionBase<T>[];
    fieldsetClass?: string[] | string;
    loopClass?: string[] | string;
    
    constructor(options: {
        value?: T,
        placeholder?: string,
        key?: string,
        label?: string,
        required?: boolean,
        order?: number,
        controlType?: string,
        type?: string,
        class?: string[] | string,
        invalidClass?: string[] | string,
        validators?: ((...args: any[]) => any) [],
        messages?: {validator: string, message: string}[],
        group?: boolean,
        questions?: QuestionBase<T>[],
        fieldsetClass?: string[] | string,
        loopClass?: string[] | string,
    } = {}) {
        super(options);
        this.questions = options['questions'] || [];
        let childs: any = null;
        if (options['questions']) childs = options['questions'].map((q) => q);
        this.childs = childs || [];
        this.fieldsetClass = options['fieldsetClass'] || '';
        this.loopClass = options['loopClass'] || '';
    }
}

export class QuestionRadio<T = string> extends QuestionBase<T> {
    override controlType = 'radio';
    override type: string;
    choix: {label?: string, labelClass?: string|string[], labelBefore?: boolean, value?: string, inputClass?: string[]|string}[];
    constructor(options: {
        value?: T;
        placeholder?: string;
        key?: string;
        label?: string;
        labelClass?: string[] | string;
        required?: boolean;
        order?: number;
        controlType?: string;
        type?: string;
        class?: string[] | string;
        options?: {key: string, value: string}[];
        invalidClass?: string[] | string;
        validators?: ((...args: any[]) => any) [];
        messages?: {validator: string, message: string}[];
        group?: boolean;
        questions?: QuestionBase<T>[];
        childs?: QuestionBase<T>[];
        choix?: {label?: string, labelClass?: string|string[], labelBefore?: boolean, value?: string, inputClass?: string[]|string}[];
        inputClass?: string[] | string;
    } = {}) {
        super(options);
        this.type = options['type'] || 'radio';
        this.choix = options['choix'] || [];
    }
}

export class QuestionTextBox<T = string> extends QuestionBase<T> {
    override controlType = 'textbox';
    override type: string;
    
    constructor(options: Options<T> = {}) {
        super(options);
        this.type = options['type'] || '';
    }
    
}

export class QuestionTextarea<T = string> extends QuestionBase<T> {
    override controlType = 'textarea';
    override type: string;
    
    constructor(options: Options<T> = {}) {
        super(options);
        this.type = options['type'] || '';
    }
    
}
