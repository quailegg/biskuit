<?php $view->script('site-index', 'system/site:app/bundle/index.js', ['vue', 'uikit-nestable']) ?>

<div id="site" class="uk-form" v-cloak>
    <div class="uk-grid pk-grid-large" data-uk-grid-margin>
        <div class="pk-width-sidebar">
            <div class="uk-panel">
                <ul class="uk-nav uk-nav-side">
                    <li class="uk-visible-hover" :class="{'uk-active': isActive(menu), 'uk-nav-divider': menu.divider}" v-for="menu in menusWithDivider">
                        <a @click="selectMenu(menu, false)" v-if="!menu.divider">{{ menu.label }}</a>
                        <ul class="uk-subnav pk-subnav-icon uk-hidden" v-if="!menu.fixed && !menu.divider">
                            <li><a class="pk-icon-edit pk-icon-hover" :title="$trans('Edit')" data-uk-tooltip="{delay: 500}" @click="editMenu(menu)"></a></li>
                            <li><a class="pk-icon-delete pk-icon-hover" :title="$trans('Delete')" data-uk-tooltip="{delay: 500}" @click="removeMenu(menu)" v-confirm="'Delete menu?'"></a></li>
                        </ul>
                    </li>
                </ul>

                <p>
                    <a class="uk-button" @click.prevent="editMenu()">{{ 'Add Menu' | trans }}</a>
                </p>
            </div>
        </div>
        <div class="pk-width-content">
            <div class="uk-margin uk-flex uk-flex-space-between uk-flex-wrap" data-uk-margin>
                <div class="uk-flex uk-flex-middle uk-flex-wrap" data-uk-margin>
                    <h2 class="uk-margin-remove">{{ menu.label | trans }}</h2>
                    <div class="uk-margin-left" v-show="selected.length">
                        <ul class="uk-subnav pk-subnav-icon">
                            <li><a class="pk-icon-check pk-icon-hover" :title="$trans('Publish')" data-uk-tooltip="{delay: 500}" @click="status(1)"></a></li>
                            <li><a class="pk-icon-block pk-icon-hover" :title="$trans('Unpublish')" data-uk-tooltip="{delay: 500}" @click="status(0)"></a></li>
                            <li v-show="showMove" data-uk-dropdown="{ mode: 'click' }">
                                <a class="pk-icon-move pk-icon-hover" :title="$trans('Move')" data-uk-tooltip="{delay: 500}" @click.prevent></a>
                                <div class="uk-dropdown uk-dropdown-small">
                                    <ul class="uk-nav uk-nav-dropdown">
                                        <li v-for="m in menusWithoutTrash"><a @click="moveNodes(m.id)">{{ m.label }}</a></li>
                                    </ul>
                                </div>
                            </li>
                            <li v-show="showDelete"><a class="pk-icon-delete pk-icon-hover" :title="$trans('Delete')" data-uk-tooltip="{delay: 500}" @click="removeNodes" v-confirm="'Delete item?'"></a></li>
                        </ul>
                    </div>
                </div>
                <div class="uk-position-relative" data-uk-margin>
                    <div data-uk-dropdown="{ mode: 'click' }">
                        <a class="uk-button uk-button-primary" @click.prevent v-show="menu.id != 'trash'">{{ 'Add Page' | trans }}</a>
                        <div class="uk-dropdown uk-dropdown-small uk-dropdown-flip">
                            <ul class="uk-nav uk-nav-dropdown">
                                <li v-for="type in unprotectedTypes">
                                    <a :href="$url.route('admin/site/page/edit', { id: type.id, menu: menu.id })">{{ type.label }}</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="uk-overflow-container">
                <div class="pk-table-fake pk-table-fake-header" :class="{'pk-table-fake-border': !tree[0]}">
                    <div class="pk-table-width-minimum pk-table-fake-nestable-padding"><input type="checkbox" v-check-all:nodes.number="{ watchedElementsSelector: 'input[name=id]', statusStorageSelector: 'selected' }"></div>
                    <div class="pk-table-min-width-100">{{ 'Title' | trans }}</div>
                    <div class="pk-table-width-100 uk-text-center">{{ 'Status' | trans }}</div>
                    <div class="pk-table-width-100">{{ 'Type' | trans }}</div>
                    <div class="pk-table-width-150">{{ 'URL' | trans }}</div>
                </div>

                <ul class="uk-nestable uk-margin-remove" ref="nestable" v-show="tree[0]">
                    <node v-for="node in tree[0]" :tree="tree" :node="node" :key="`${node.id}`" v-model="selected"></node>
                </ul>
            </div>
            <h3 class="uk-h1 uk-text-muted uk-text-center" v-show="tree && !tree[0]">{{ 'No pages found.' | trans }}</h3>
        </div>
    </div>

    <v-modal ref="modal">
        <validation-observer v-slot="{ handleSubmit }" slim>
            <form class="uk-form uk-form-stacked" @submit.prevent="handleSubmit(function() { saveMenu(edit); })">
                <div class="uk-modal-header">
                    <h2>{{ 'Add Menu' | trans }}</h2>
                </div>

                <v-validated-input
                    id="form-name"
                    name="label"
                    rules="required"
                    label="Name"
                    :error-messages="{ required: 'Name cannot be blank.' }"
                    :options="{ elementClass: 'uk-width-1-1 uk-form-large' }"
                    v-model="edit.label">
                </v-validated-input>

                <div class="uk-form-row">
                    <span class="uk-form-label">{{ 'Menu Positions' | trans }}</span>
                    <div class="uk-form-controls uk-form-controls-text">
                        <p class="uk-form-controls-condensed" v-for="m in config.menus">
                            <label><input type="checkbox" :value="m.name" v-model="edit.positions"> {{ m.label }}</label> <span class="uk-text-muted" v-if="getMenu(m.name) && getMenu(m.name).id != edit.id">{{ '(Currently set to: %menu%)' | trans({ menu: getMenu(m.name).label }) }}</span>
                        </p>
                    </div>
                </div>

                <div class="uk-modal-footer uk-text-right">
                    <button class="uk-button uk-button-link uk-modal-close" type="button" @click.prevent="cancel">{{ 'Cancel' | trans }}</button>
                    <button class="uk-button uk-button-link" :disabled="!edit.label">{{ 'Save' | trans }}</button>
                </div>
            </form>
        </validation-observer>
    </v-modal>
</div>

<script id="node" type="text/template">
    <li class="uk-nestable-item check-item" :class="{'uk-parent': tree[node.id], 'uk-active': $root.isSelected(node)}" :data-id="node.id">
        <div class="uk-nestable-panel pk-table-fake uk-form uk-visible-hover">
            <div class="pk-table-width-minimum pk-table-collapse">
                <div class="uk-nestable-toggle" data-nestable-action="toggle"></div>
            </div>
            <div class="pk-table-width-minimum"><input type="checkbox" name="id" :value="node.id" v-model.number="selected"></div>
            <div class="pk-table-min-width-100">
                <a :href="$url.route('admin/site/page/edit', { id: node.id })">{{ node.title }}</a>
                <span class="uk-text-muted uk-text-small uk-margin-small-left" v-if="node.data.menu_hide">{{ 'Hidden' | trans }}</span>
            </div>
            <div class="pk-table-width-minimum">
                <a class="pk-icon-home pk-icon-hover uk-invisible" :title="$trans('Set as frontpage')" data-uk-tooltip="{delay: 500}" v-if="!isFrontpage && node.status && type.frontpage !== false" @click="setFrontpage"></a>
                <i class="pk-icon-home-active pk-icon-muted uk-float-right" :title="$trans('Frontpage')" v-if="isFrontpage"></i>
            </div>
            <div class="pk-table-width-100 uk-text-center">
                <td class="uk-text-center">
                    <a :class="{'pk-icon-circle-danger': !node.status, 'pk-icon-circle-success': node.status}" @click="toggleStatus"></a>
                </td>
            </div>
            <div class="pk-table-width-100">{{ type.label }}</div>
            <div class="pk-table-width-150 pk-table-max-width-150 uk-text-truncate">
                <a :title="node.url" target="_blank" :href="$url.route(node.url.substr(0,1) == '/' ? node.url.substr(1) : node.url.substr(0))" v-if="node.accessible && node.url">{{ decodeURI(node.url) }}</a>
                <span v-else>{{ node.path }}</span>
            </div>
        </div>

        <ul class="uk-nestable-list" v-show="tree[node.id]">
            <node v-for="childNode in tree[node.id]" :tree="tree" :node="childNode" :key="`${childNode.id}`" v-model="selected"></node>
        </ul>
    </li>
</script>
