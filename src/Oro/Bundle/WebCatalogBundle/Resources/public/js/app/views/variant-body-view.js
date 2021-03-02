import BaseView from 'oroui/js/app/views/base/view';
import LoadingMaskView from 'oroui/js/app/views/loading-mask-view';
import layout from 'oroui/js/layout';
import _ from 'underscore';

const VariantBodyView = BaseView.extend({
    options: {},
    selectors: {
        container: null,
        trigger: null,
        body: null
    },
    expanded: false,
    loadingMaskView: null,
    $trigger: null,
    $container: null,

    /**
     * @inheritDoc
     */
    constructor: function VariantBodyView(options) {
        VariantBodyView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritDoc
     */
    initialize(options) {
        this.options = _.defaults(options || {}, this.options);
        this.initLayout().done(_.bind(this.handleLayoutInit, this));
    },

    handleLayoutInit() {
        this.$trigger = this.options.el.closest('.content-variant-item').find(this.options.selectors.trigger);
        this.$container = this.options.el.closest('.content-variant-item').find(this.options.selectors.container);
        this.loadingMaskView = new LoadingMaskView({container: this.$container});

        this.initializeCollapsedState();
        this.$trigger.on('click', _.bind(this.onToggle, this));
        layout.initPopover(this.$container);
    },

    onToggle(e) {
        if (this.$container.find(this.options.selectors.body).length !== 0) {
            return;
        }

        if (this.$container.hasClass('show')) {
            return;
        }

        this.loadingMaskView.show();
        this.loadBody();
    },

    initializeCollapsedState() {
        if (this.options.expanded) {
            this.loadingMaskView.show();
            this.$container.addClass('show');
            this.loadBody();
        } else {
            this.$trigger.addClass('collapsed');
        }
    },

    loadBody() {
        const bodyPrototype = this.$el.data('body-prototype');
        if (bodyPrototype) {
            this.$container
                .append(bodyPrototype)
                .trigger('content:changed');

            this.validate();
        }
        this.loadingMaskView.hide();
    },

    validate() {
        const $form = this.$el.closest('form');
        if ($form.data('validator')) {
            $form.validate();
        }
    }
});

export default VariantBodyView;
