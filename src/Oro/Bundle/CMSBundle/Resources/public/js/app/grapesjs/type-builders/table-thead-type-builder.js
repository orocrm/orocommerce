import BaseTypeBuilder from 'orocms/js/app/grapesjs/type-builders/base-type-builder';
import TableTypeDecorator from '../controls/table-edit/table-type-decorator';

const TableTheadTypeBuilder = BaseTypeBuilder.extend({
    parentType: 'thead',

    modelMixin: {
        defaults: {
            removable: false,
            copyable: false,
            draggable: false
        },

        ...TableTypeDecorator
    },

    constructor: function TableTheadTypeBuilder(...args) {
        TableTheadTypeBuilder.__super__.constructor.apply(this, args);
    },

    isComponent(el) {
        return el.nodeType === el.ELEMENT_NODE && el.tagName === 'THEAD';
    }
});

export default TableTheadTypeBuilder;
