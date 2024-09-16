import $ from 'jquery';
import __ from 'orotranslation/js/translator';
import BaseView from 'oroui/js/app/views/base/view';
import DeleteConfirmation from 'oroui/js/delete-confirmation';
import viewportManager from 'oroui/js/viewport-manager';
import ShoppinglistAddNotesModalView from './shoppinglist-add-notes-modal-view';

const ENTER_KEY_CODE = 13;
const ESCAPE_KEY_CODE = 27;

const ShoppingListOwnerInlineEditableView = BaseView.extend({
    options: {
        loadingClass: 'loading-blur-overlay'
    },

    events: {
        'click [data-role="apply"]': 'saveNote',
        'click [data-role="edit-notes"]': 'editNote',
        'click [data-role="remove-notes"]': 'removeNote',
        'click [data-role="add-notes"]': 'addNote',
        'click [data-role="decline"]': 'undoChanges',
        'input textarea': 'onInput',
        'keydown textarea': 'onKeydown'
    },

    listen: {
        'change:notes model': 'onNotesChanged'
    },

    constructor: function ShoppingListOwnerInlineEditableView(options) {
        ShoppingListOwnerInlineEditableView.__super__.constructor.call(this, options);
    },

    initialize(options) {
        this.options = Object.assign({}, options || {}, this.options);
        this.validator = this.$('form').validate();
        ShoppingListOwnerInlineEditableView.__super__.initialize.call(this, options);
    },

    onKeydown(e) {
        if (
            !this.$('[data-role="apply"]').is(':disabled') &&
            (e.keyCode === ENTER_KEY_CODE && e.ctrlKey)
        ) {
            this.saveNote();
            this.getVisibleAction().trigger('focus');
        } else if (e.keyCode === ESCAPE_KEY_CODE) {
            this.undoChanges();
            this.getVisibleAction().trigger('focus');
        }
    },

    onInput(e) {
        this.$('[data-role="apply"]').attr('disabled', e.target.value === this.model.get('notes'));
    },

    createPopupForm(notes) {
        const shoppingListAddNotesModalView = new ShoppinglistAddNotesModalView({
            title: __(`oro.frontend.shoppinglist.dialog.notes.add`, {
                shoppingList: this.options.shoppingListLabel
            }),
            okText: __(`oro.frontend.shoppinglist.dialog.notes.add_btn_label`),
            cancelText: __('Cancel'),
            okCloses: false,
            notes
        });
        this.subview('shoppingListAddNotesModalView', shoppingListAddNotesModalView);

        shoppingListAddNotesModalView.on('ok', () => {
            if (shoppingListAddNotesModalView.isValid()) {
                this.saveModel({
                    notes: shoppingListAddNotesModalView.getValue()
                });
                shoppingListAddNotesModalView.close();
            }
        });

        shoppingListAddNotesModalView.open();
    },

    addNote() {
        this.createPopupForm();
    },

    onNotesChanged(model, newValue) {
        this.updateNotesText(newValue);
        this.updateTexAreaValue(newValue);
        this.hideEditForm();
        this.switchActions();
    },

    saveNote() {
        if (!this.isValid()) {
            return;
        }

        const newValue = this.$('textarea').val().trim();

        this.$el.addClass(this.options.loadingClass);
        this.saveModel({
            notes: newValue
        });
    },

    saveModel(value) {
        this.model.save(value, {
            patch: true,
            wait: false,
            success: resp => {
                this.$el.removeClass(this.options.loadingClass);
            },
            error: err => {
                this.model.set('notes', this.model.previous('notes'));
                this.updateTexAreaValue(this.model.get('notes'));
                this.updateNotesText(this.model.get('notes'));
                this.showEditForm();
                this.$el.removeClass(this.options.loadingClass);
            }
        });
    },

    editNote() {
        if (viewportManager.isApplicable('mobile-big')) {
            return this.createPopupForm(this.model.get('notes'));
        }
        this.hideActions();
        this.showEditForm();
    },

    removeNote() {
        const confirm = new DeleteConfirmation({
            title: false,
            content: __('oro.frontend.shoppinglist.dialog.notes.remove_title', {
                shoppingList: this.options.shoppingListLabel
            }),
            okText: __('oro.frontend.shoppinglist.dialog.notes.delete_btn')
        });
        this.subview('confirm', confirm);
        this.listenTo(confirm, 'ok', () => this.saveModel({
            notes: ''
        }));
        confirm.open();
    },

    undoChanges() {
        this.hideEditForm();
        this.updateTexAreaValue(this.model.get('notes'));
        this.switchActions();
    },

    showEditForm() {
        this.$('[data-role="edit-notes-form"]').removeClass('hide');
        this.$('textarea').trigger('focus');
    },

    hideEditForm() {
        this.$('[data-role="apply"]').attr('disabled', true);
        this.$('[data-role="edit-notes-form"]').addClass('hide');
    },

    hideActions() {
        this.$('[data-role="view-notes"]').addClass('hide');
        this.$('[data-role="add-notes]').addClass('hide');
    },

    switchActions() {
        if (this.model.isEmptyNotes()) {
            this.$('[data-role="add-notes"]').removeClass('hide');
            this.$('[data-role="view-notes"]').addClass('hide');
        } else {
            this.$('[data-role="view-notes"]').removeClass('hide');
            this.$('[data-role="add-notes"]').addClass('hide');
        }
    },

    getVisibleAction() {
        return this.$('[data-role="edit-notes"], [data-role="add-notes"]').filter((i, el) => $(el).is(':visible'));
    },

    updateNotesText(val) {
        if (val !== void 0) {
            this.$('[data-role="notes-text"]').text(val);
        }
    },

    updateTexAreaValue(val) {
        if (val !== void 0) {
            this.$('textarea').val(val);
        }
    },

    isValid() {
        return this.validator.form();
    },

    dispose() {
        if (this.disposed) {
            return;
        }

        delete this.validator;
        return ShoppingListOwnerInlineEditableView.__super__.dispose.call(this);
    }
});

export default ShoppingListOwnerInlineEditableView;
