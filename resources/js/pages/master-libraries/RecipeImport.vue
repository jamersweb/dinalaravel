<template>
    <div class="main">
        <Loader v-if="pageLoading" :loadingText="loaderText" />
        <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail" />
        <div class="col-12 topbar pt-2 ps-3">
            <h4 class="col-8 float-start pt-0" style="font-size:26px;">Recipe Import</h4>
        </div>
        <div class="col-12 content p-3">
            <div class="import-layout">
                <section class="panel">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 fw-bold">Scrape Recipes</h5>
                        <span class="small text-muted">{{ urlCount }} URL{{ urlCount === 1 ? '' : 's' }}</span>
                    </div>
                    <textarea
                        v-model="form.urls"
                        class="form-control mt-3 url-box"
                        placeholder="Paste one recipe URL per line. You can also paste comma-separated URLs."
                    ></textarea>

                    <div class="mt-3">
                        <h6 class="fw-bold">Default Meal Type</h6>
                        <div class="d-flex flex-wrap gap-3">
                            <label v-for="item in mealTypes" :key="item" class="check-label text-capitalize">
                                <input class="form-check-input me-1" type="checkbox" :value="item" v-model="form.default_suitable_for">
                                {{ item }}
                            </label>
                        </div>
                    </div>

                    <div class="mt-3">
                        <h6 class="fw-bold">Attach Existing Tags</h6>
                        <input v-model="tagSearch" class="form-control form-control-sm mb-2" placeholder="Search meal tags">
                        <div class="tag-picker">
                            <label v-for="tag in filteredTags" :key="tag.id" class="check-label">
                                <input class="form-check-input me-1" type="checkbox" :value="tag.id" v-model="form.tag_ids">
                                {{ tag.name }}
                            </label>
                            <p v-if="filteredTags.length < 1" class="small text-muted mb-0">No tags found.</p>
                        </div>
                    </div>

                    <div class="mt-3 d-flex flex-column gap-2">
                        <label class="check-label">
                            <input class="form-check-input me-1" type="checkbox" v-model="form.create_missing_tags">
                            Create missing tags from recipe cuisine/category/keywords
                        </label>
                        <label class="check-label">
                            <input class="form-check-input me-1" type="checkbox" v-model="form.import_images">
                            Download recipe images when possible
                        </label>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="button" class="btn1" @click="previewRecipes">Preview</button>
                        <button type="button" class="btn3 px-3" :disabled="validPreviewCount < 1" @click="importRecipes">Import Valid</button>
                    </div>
                </section>

                <section class="panel results-panel">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 fw-bold">Preview</h5>
                        <span class="small text-muted">{{ validPreviewCount }} valid</span>
                    </div>

                    <div v-if="previews.length < 1" class="empty-state">
                        Paste recipe URLs and click Preview.
                    </div>

                    <div v-for="item in previews" :key="item.url" class="preview-item mt-3" :class="{failed: !item.status}">
                        <div class="preview-image" :style="previewImageStyle(item)"></div>
                        <div class="preview-body">
                            <div class="d-flex justify-content-between gap-2">
                                <h6 class="fw-bold mb-1">{{ item.name || item.url }}</h6>
                                <span class="badge" :class="item.status ? 'bg-success' : 'bg-danger'">{{ item.status ? 'Ready' : 'Failed' }}</span>
                            </div>
                            <p class="small text-muted mb-2">{{ item.url }}</p>
                            <p v-if="!item.status" class="text-danger small mb-0">{{ item.message }}</p>
                            <template v-else>
                                <div class="metrics">
                                    <span>{{ item.no_of_servings || 1 }} servings</span>
                                    <span>{{ item.calories_per_serving || 0 }} cal</span>
                                    <span>{{ item.protein_per_serving || 0 }}g protein</span>
                                    <span>{{ item.carbs_per_serving || 0 }}g carbs</span>
                                    <span>{{ item.fat_per_serving || 0 }}g fat</span>
                                    <span>{{ item.fiber_per_serving || 0 }}g fiber</span>
                                </div>
                                <p class="small mb-1 mt-2">{{ item.ingredients.length }} ingredients, {{ item.directions.length }} steps</p>
                                <div class="d-flex flex-wrap gap-1">
                                    <span v-for="tag in item.tag_suggestions" :key="tag" class="suggested-tag">{{ tag }}</span>
                                </div>
                                <p v-for="warning in item.warnings" :key="warning" class="text-warning small mb-0 mt-1">{{ warning }}</p>
                            </template>
                        </div>
                    </div>
                </section>
            </div>

            <section v-if="importResult" class="panel mt-3">
                <h5 class="fw-bold">Import Result</h5>
                <p class="mb-1">Imported: {{ importResult.imported.length }}</p>
                <p class="mb-1">Skipped: {{ importResult.skipped.length }}</p>
                <p class="mb-0">Failed: {{ importResult.failed.length }}</p>
            </section>
        </div>
    </div>
</template>

<script>
import axios from "axios";
import config from "../../config";
import Loader from "../../components/loader.vue";
import Inform from "../../components/inform.vue";

export default {
    components: { Loader, Inform },
    data() {
        return {
            apiConfig: {
                headers: {
                    Authorization: "Bearer " + config.storage.getItem("fwd_session_token"),
                },
            },
            mealTypes: ['breakfast', 'lunch', 'dinner', 'snacks', 'drinks'],
            form: {
                urls: '',
                default_suitable_for: ['dinner'],
                tag_ids: [],
                create_missing_tags: true,
                import_images: true,
            },
            tags: [],
            tagSearch: '',
            previews: [],
            importResult: null,
            pageLoading: false,
            loaderText: null,
            informModal: false,
            modalTitle: '',
            modalDetail: '',
        };
    },
    computed: {
        urlCount() {
            return this.urlsList.length;
        },
        urlsList() {
            return this.form.urls
                .split(/\r\n|\r|\n|,/)
                .map((url) => url.trim())
                .filter(Boolean);
        },
        validPreviewCount() {
            return this.previews.filter((item) => item.status).length;
        },
        filteredTags() {
            const query = this.tagSearch.trim().toLowerCase();
            if (!query) return this.tags;
            return this.tags.filter((tag) => tag.name.toLowerCase().includes(query));
        },
    },
    created() {
        this.getTags();
    },
    methods: {
        payload() {
            return {
                urls: this.urlsList,
                default_suitable_for: this.form.default_suitable_for,
                tag_ids: this.form.tag_ids,
                create_missing_tags: this.form.create_missing_tags,
                import_images: this.form.import_images,
            };
        },
        async getTags() {
            const res = await axios.get(config.baseApiUrl + 'get-tags?category=meal', this.apiConfig);
            if (!res.data.status) return;
            const grouped = res.data.data || [];
            this.tags = grouped.flatMap((group) => group.tagList || []);
        },
        validateForm() {
            if (this.urlsList.length < 1) {
                this.showInform('Missing URLs', 'Paste at least one recipe URL.');
                return false;
            }
            if (this.form.default_suitable_for.length < 1) {
                this.showInform('Meal Type Required', 'Select at least one default meal type.');
                return false;
            }
            return true;
        },
        async previewRecipes() {
            if (!this.validateForm()) return;
            this.pageLoading = true;
            this.loaderText = 'Scraping recipes';
            this.importResult = null;
            try {
                const res = await axios.post(config.baseApiUrl + 'recipe-import/preview', this.payload(), this.apiConfig);
                if (res.data.status) {
                    this.previews = res.data.data || [];
                } else {
                    this.showInform('Preview Failed', res.data.message || 'Unable to preview recipes.');
                }
            } catch (error) {
                this.showInform('Preview Failed', this.errorMessage(error));
            } finally {
                this.pageLoading = false;
            }
        },
        async importRecipes() {
            if (!this.validateForm()) return;
            this.pageLoading = true;
            this.loaderText = 'Importing recipes';
            try {
                const res = await axios.post(config.baseApiUrl + 'recipe-import/import', this.payload(), this.apiConfig);
                if (res.data.status) {
                    this.importResult = res.data.data;
                    this.showInform('Import Complete', res.data.message || 'Recipes imported.');
                } else {
                    this.showInform('Import Failed', res.data.message || 'Unable to import recipes.');
                }
            } catch (error) {
                this.showInform('Import Failed', this.errorMessage(error));
            } finally {
                this.pageLoading = false;
            }
        },
        previewImageStyle(item) {
            if (!item.image_url) return {};
            return { backgroundImage: `url(${item.image_url})` };
        },
        showInform(title, detail) {
            this.modalTitle = title;
            this.modalDetail = detail;
            this.informModal = true;
            setTimeout(() => {
                this.informModal = false;
            }, 2500);
        },
        errorMessage(error) {
            return error?.response?.data?.message || error?.message || 'Unexpected error.';
        },
    },
};
</script>

<style scoped>
.content {
    height: calc(100% - 60px);
    overflow-y: auto;
}

.import-layout {
    display: grid;
    grid-template-columns: minmax(320px, 420px) 1fr;
    gap: 16px;
}

.panel {
    background: #fff;
    border: 1px solid #e5e5e5;
    border-radius: 8px;
    padding: 16px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.url-box {
    min-height: 180px;
    resize: vertical;
}

.tag-picker {
    max-height: 150px;
    overflow-y: auto;
    border: 1px solid #eee;
    border-radius: 6px;
    padding: 8px;
}

.check-label {
    font-size: 13px;
    display: inline-flex;
    align-items: center;
    margin-right: 12px;
    margin-bottom: 6px;
}

.results-panel {
    min-height: 420px;
}

.empty-state {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 280px;
    color: #8c8c8c;
    font-size: 14px;
}

.preview-item {
    display: grid;
    grid-template-columns: 150px 1fr;
    gap: 14px;
    border: 1px solid #ececec;
    border-radius: 8px;
    padding: 10px;
}

.preview-item.failed {
    border-color: #f3b8b8;
}

.preview-image {
    width: 150px;
    min-height: 120px;
    border-radius: 6px;
    background: #f5f5f5 center / cover no-repeat;
}

.preview-body {
    min-width: 0;
}

.metrics {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.metrics span,
.suggested-tag {
    background: #f2f2f2;
    border-radius: 999px;
    padding: 3px 8px;
    font-size: 11px;
}

@media (max-width: 1100px) {
    .import-layout {
        grid-template-columns: 1fr;
    }
}
</style>
