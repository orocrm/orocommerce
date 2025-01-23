define(function(require) {
    'use strict';

    const _ = require('underscore');
    const __ = require( 'orotranslation/js/translator');
    const mediator = require('oroui/js/mediator');
    const messenger = require('oroui/js/messenger');
    const routing = require('routing');
    const DeleteAction = require('oro/datagrid/action/delete-action');

    /**
     * Delete action with confirm dialog, triggers REST DELETE request
     *
     * @export  oro/datagrid/action/delete-product-action
     * @class   oro.datagrid.action.DeleteProductAction
     * @extends oro.datagrid.action.DeleteAction
     */
    const DeleteProductAction = DeleteAction.extend({
        /**
         * @inheritdoc
         */
        constructor: function DeleteProductAction(options) {
            DeleteProductAction.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        getConfirmDialogOptions() {
            return {
                title: this.getConfirmContentTitle(),
                content: this.getConfirmContentMessage(),
                okText: __(this.messages.confirm_ok),
                cancelText: __(this.messages.confirm_cancel)
            };
        },

        /**
         * Get confirm content title
         *
         * @return {String}
         */
        getConfirmContentTitle() {
            return __(
                this.messages.confirm_title,
                _.mapObject(this.model.toJSON(), value => _.isString(value) ? _.escape(value) : value)
            );
        },

        toggleLoadingOverlay() {
            if (typeof this.model.subModels === 'function') {
                for (const subModel of this.model.subModels()) {
                    if (typeof subModel.toggleLoadingOverlay === 'function') {
                        subModel.toggleLoadingOverlay(true);
                    }
                }
            }

            if (typeof this.model.toggleLoadingOverlay === 'function') {
                this.model.toggleLoadingOverlay(true);
            }
        },

        /**
         * @inheritdoc
         */
        doDelete() {
            const success = __(this.messages.success, this.model.toJSON());
            const $datagridEl = this.datagrid.$el;

            $datagridEl.trigger('ajaxStart');
            this.toggleLoadingOverlay();

            if (this.request) {
                this.request.abort();
            }

            this.request = this.model.destroy({
                url: this.getLink(),
                wait: true,
                reset: false,
                uniqueOnly: true,
                toggleLoading: false,
                // to prevent main application loading bar from been shown
                global: this.configuration.showGlobalLoadingBar ?? true,
                success: (model, response, options) => {
                    messenger.notificationFlashMessage('success', success, {namespace: 'shopping_list'});
                    mediator.trigger('shopping-list:refresh', options);
                },
                complete: () => {
                    $datagridEl.trigger('ajaxComplete');
                }
            });
        },

        /**
         * @inheritdoc
         */
        getLink: function() {
            if (this.model.attributes.isConfigurable) {
                return routing.generate('oro_api_shopping_list_frontend_delete_line_item_configurable', {
                    shoppingListId: this.datagrid.metadata.gridParams.shopping_list_id,
                    productId: this.model.attributes.productId,
                    unitCode: this.model.attributes.unit
                });
            } else {
                return routing.generate('oro_api_shopping_list_frontend_delete_line_item', {
                    id: this.model.attributes.id,
                    onlyCurrent: this.model.attributes.variantId || this.model.attributes.isKit ? 1 : 0
                });
            }
        }
    });

    return DeleteProductAction;
});
