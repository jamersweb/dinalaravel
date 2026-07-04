<template>
    <div v-if="open" class="my-popup-component" @click.self="$emit('cancel')">
        <div class="brds-2 position-relative text-start p-4" style="width: 420px; background-color: white;">
            <button class="trans_btn float-end" @click="$emit('cancel')" style="font-size: 25px;">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <h5 class="fw-bold mb-2">Tag workout routine</h5>
            <p class="h8 text-muted mb-3" v-if="routineTitle">
                Adding <strong>{{ routineTitle }}</strong> as a grouped section.
            </p>
            <label class="h8 fw-bold mb-1">Section tag</label>
            <select v-model="localTag" class="form-select mb-3">
                <option v-for="tag in tags" :key="tag.id" :value="tag.id">{{ tag.label }}</option>
            </select>
            <div class="text-end">
                <button class="scnd_btn py-1 px-3 brds-1 me-2" @click="$emit('cancel')">Cancel</button>
                <button class="prim_btn py-1 px-3 brds-1" @click="$emit('confirm', localTag)">Add routine</button>
            </div>
        </div>
    </div>
</template>

<script>
import { ROUTINE_SECTION_TAGS } from '../../utils/workoutRoutineHelpers';

export default {
    props: {
        open: { type: Boolean, default: false },
        routineTitle: { type: String, default: '' },
        defaultTag: { type: String, default: 'warm_up' },
    },
    emits: ['cancel', 'confirm'],
    data() {
        return {
            tags: ROUTINE_SECTION_TAGS,
            localTag: this.defaultTag,
        };
    },
    watch: {
        open(value) {
            if (value) {
                this.localTag = this.defaultTag;
            }
        },
        defaultTag(value) {
            this.localTag = value;
        },
    },
};
</script>
