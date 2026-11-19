/*
 * Theme entry point: Bootstrap 5, AdminLTE 4 and one Stimulus application for Sonata's and the theme's controllers.
 * Exposes window.bootstrap (applications must not bundle Bootstrap a second time) and window.sonataApplication
 * (other scripts register their controllers on it, e.g. the datepicker of the form extensions), plus window.stimulus.
 */
import '../scss/app.scss';
import '@fortawesome/fontawesome-free/css/all.css';
import '@fortawesome/fontawesome-free/css/v4-shims.css';
import '@fontsource/source-sans-3/300.css';
import '@fontsource/source-sans-3/400.css';
import '@fontsource/source-sans-3/400-italic.css';
import '@fontsource/source-sans-3/600.css';
import '@fontsource/source-sans-3/700.css';
import 'tom-select/dist/css/tom-select.bootstrap5.css';
import '../scss/tom-select.scss';

import * as bootstrap from 'bootstrap';
import 'admin-lte';
import * as stimulus from '@hotwired/stimulus';

import CollectionController from './sonata/collection_controller';
import ConfirmExitController from './sonata/confirm_exit_controller';
import FilterController from './sonata/filter_controller';
import FilterListController from './sonata/filter_list_controller';
import PerPageController from './sonata/per_page_controller';
import ReadmoreController from './sonata/readmore_controller';
import RevisionController from './sonata/revision_controller';

import AssociationController from './controllers/association_controller';
import AutocompleteController from './controllers/autocomplete_controller';
import BackController from './controllers/back_controller';
import BatchController from './controllers/batch_controller';
import ChoiceFieldMaskController from './controllers/choice_field_mask_controller';
import ColorModeController from './controllers/color_mode_controller';
import EditController from './controllers/edit_controller';
import InlineEditController from './controllers/inline_edit_controller';
import SelectController from './controllers/select_controller';
import SidebarController from './controllers/sidebar_controller';
import SidebarMenuController from './controllers/sidebar_menu_controller';
import ClampController from './controllers/clamp_controller';
import SortableController from './controllers/sortable_controller';

window.bootstrap = bootstrap;

const application = stimulus.Application.start();

application.register('sonata-collection', CollectionController);
application.register('sonata-confirm-exit', ConfirmExitController);
application.register('sonata-filter', FilterController);
application.register('sonata-filter-list', FilterListController);
application.register('sonata-per-page', PerPageController);
application.register('sonata-readmore', ReadmoreController);
application.register('sonata-revision', RevisionController);

application.register('sonata-association', AssociationController);
application.register('sonata-autocomplete', AutocompleteController);
application.register('sonata-batch', BatchController);
application.register('sonata-choice-field-mask', ChoiceFieldMaskController);
application.register('sonata-edit', EditController);
application.register('sonata-inline-edit', InlineEditController);
application.register('sonata-select', SelectController);
application.register('sonata-sortable', SortableController);

application.register('adminlte4-back', BackController);
application.register('adminlte4-color-mode', ColorModeController);
application.register('adminlte4-sidebar', SidebarController);
application.register('adminlte4-sidebar-menu', SidebarMenuController);
application.register('adminlte4-clamp', ClampController);

// global.stimulus: scripts built against Sonata's app.js (e.g. the form extensions) take Stimulus from there
window.stimulus = stimulus;
window.sonataApplication = application;
