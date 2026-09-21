{!! view_render_event('admin.dashboard.index.top_tire_sizes.before') !!}

<!-- Top Tire Sizes Vue Component -->
<v-dashboard-top-tire-sizes>
    <!-- Shimmer -->
    <x-admin::shimmer.dashboard.index.top-selling-products />
</v-dashboard-top-tire-sizes>

{!! view_render_event('admin.dashboard.index.top_tire_sizes.after') !!}

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-dashboard-top-tire-sizes-template"
    >
        <!-- Shimmer -->
        <template v-if="isLoading">
            <x-admin::shimmer.dashboard.index.top-selling-products />
        </template>

        <!-- Top Tire Sizes Section -->
        <template v-else>
            <div class="w-full rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between gap-2 p-4">
                    <p class="text-base font-semibold text-gray-600 dark:text-gray-300">
                        @lang('admin::app.dashboard.index.top-tire-sizes.title')
                    </p>

                    <!-- Row Count Selector -->
                    <select
                        class="cursor-pointer rounded-md border border-gray-200 px-2 py-1 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                        v-model="limit"
                        @change="getStats(lastFilters)"
                    >
                        @foreach (\Webkul\Admin\Helpers\Dashboard::TIRE_SIZE_LIMITS as $rowLimit)
                            <option value="{{ $rowLimit }}">
                                @lang('admin::app.dashboard.index.top-tire-sizes.top-count', ['count' => $rowLimit])
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Top Tire Sizes Details -->
                <div
                    class="flex flex-col"
                    v-if="report.statistics.length"
                >
                    <div
                        class="flex gap-2.5 border-b p-4 last:border-b-0 dark:border-gray-800"
                        v-for="item in report.statistics"
                    >
                        <!-- Tire Size Details -->
                        <div class="flex w-full flex-col gap-1.5">
                            <p
                                class="text-gray-600 dark:text-gray-300"
                                v-text="item.name"
                            >
                            </p>

                            <div class="flex justify-between">
                                <p class="font-medium text-gray-800 dark:text-white">
                                    @{{ "@lang('admin::app.dashboard.index.top-tire-sizes.leads-count')".replace(':count', item.total_leads) }}
                                </p>

                                <p class="font-normal text-gray-800 dark:text-white">
                                    @{{ item.formatted_value }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty Tire Sizes Design -->
                <div
                    class="flex flex-col gap-8 p-4"
                    v-else
                >
                    <div class="grid justify-center justify-items-center gap-3.5 py-2.5">
                        <!-- Placeholder Image -->
                        <img
                            src="{{ vite()->asset('images/empty-placeholders/products.svg') }}"
                            class="dark:mix-blend-exclusion dark:invert"
                        >

                        <!-- Empty Information -->
                        <div class="flex flex-col items-center">
                            <p class="text-base font-semibold text-gray-400">
                                @lang('admin::app.dashboard.index.top-tire-sizes.empty-title')
                            </p>

                            <p class="text-gray-400">
                                @lang('admin::app.dashboard.index.top-tire-sizes.empty-info')
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </script>

    <script type="module">
        app.component('v-dashboard-top-tire-sizes', {
            template: '#v-dashboard-top-tire-sizes-template',

            data() {
                return {
                    report: [],

                    isLoading: true,

                    limit: {{ \Webkul\Admin\Helpers\Dashboard::TIRE_SIZE_LIMITS[0] }},

                    /**
                     * Kept so changing the row count re-applies the date range
                     * the user picked, instead of falling back to the default.
                     */
                    lastFilters: {},
                }
            },

            mounted() {
                this.getStats({});

                this.$emitter.on('reporting-filter-updated', this.getStats);
            },

            methods: {
                getStats(filters) {
                    this.isLoading = true;

                    this.lastFilters = Object.assign({}, filters);

                    var filters = Object.assign({}, this.lastFilters);

                    filters.type = 'top-tire-sizes';

                    filters.limit = this.limit;

                    this.$axios.get("{{ route('admin.dashboard.stats') }}", {
                            params: filters
                        })
                        .then(response => {
                            this.report = response.data;

                            this.isLoading = false;
                        })
                        .catch(error => {});
                }
            }
        });
    </script>
@endPushOnce
