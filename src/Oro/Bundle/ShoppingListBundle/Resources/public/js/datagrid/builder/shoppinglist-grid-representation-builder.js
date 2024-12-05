import TogglePaginationView from 'orodatagrid/js/app/views/toggle-pagination-view';
import ToggleGroupView from 'orodatagrid/js/app/views/toggle-group-view';

const shoppingListGridRepresentationBuilder = {
    /**
     * Init() function is required
     */
    init: (deferred, options) => {
        options.gridPromise.done(grid => {
            const topToolBar = grid.toolbars.top;

            if (topToolBar) {
                const togglePaginationView = new TogglePaginationView({
                    datagrid: grid,
                    translationPrefix: 'oro_frontend.btn'
                });
                const toggleGroupView = new ToggleGroupView({
                    datagrid: grid,
                    translationPrefix: 'oro_frontend.btn'
                });

                topToolBar.$('[data-grid-extra-actions-panel]').after(togglePaginationView.render().$el);
                togglePaginationView.$el.before(toggleGroupView.render().$el);
            }
        });

        return deferred.resolve();
    }
};

export default shoppingListGridRepresentationBuilder;
