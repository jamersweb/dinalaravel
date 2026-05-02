<template>
    <div class="cdxzbc shadow-lg" ref="target">
        <div class="emoji_picker">
            <div class="picker_container">
                <button class="trans_btn position-absolute" @click="quitEmojiPicker"
                    style="right:5px;top:5px;font-size:25px">
                    <i class="fa-solid fa-xmark"></i>
                </button>
                <div class="category" v-for="category in categories" :key="`category_${category}`">
                    <span>{{ category }}</span>
                    <div class="emojis_container">
                        <button @click="handleEmojiClick($event, emoji)"
                            v-for="(emoji, index) in category_emojis(category)" :key="`emoji_${index}`">
                            {{ emoji }}
                        </button>
                    </div>
                </div>
            </div>
            <div class="bottom_arrow" v-if="show_arrow"></div>
        </div>
    </div>
</template>

<script>

/**
 * Emoji Picker
 * Load emojis and  categories from the json file 'emojis-data.json'
 * Events:
 *  - 'emoji_click' event is fires when the user clicks on an emoji. The emoji is sent as event payload.
 * Props:
 * 	- 'show_arrow' boolean to show or not the arrow at the bottom of the picker. True by default.
 */

import data from './emojis-data.json';

export default {
    props:
    {
        show_arrow:
        {
            type: Boolean,
            required: false,
            default: true
        }
    },
    data() {
        return {
            i: 0,
        }
    },
    mounted() {
        // document.addEventListener('click',(e) => {
        // 	if(this.i==0){
        // 		this.i++;
        // 		return;
        // 	}
        // 	let child = e.target;
        // 	let parent = this.$refs.target;
        // 	var node = child.parentNode;
        // 	while (node != null) {
        // 		if (node == parent) {
        // 			return;
        // 		}
        // 		node = node.parentNode;
        // 	}
        // 	this.i=0;
        // 	this.$parent.closeEmojiInput();
        // });
    },
    beforeUnmount() {
        this.i = 0;
    },
    computed:
    {
        categories() {
            return Object.keys(data);
        },

        category_emojis: () => (category) => {
            return Object.values(data[category]);
        }
    },
    methods:
    {
        quitEmojiPicker() {
            this.$parent.toogleEmojiPicker();
        },
        handleEmojiClick(e, emoji) {
            e.preventDefault();
            this.$parent.getEmojiInput(emoji);
        }
    }
}
</script>

<style scoped>
.cdxzbc {
    position: absolute;
    z-index: 500;
    bottom: 120%;
    right: -130px;
}

.emoji_picker {
    position: relative;
    display: flex;
    flex-direction: column;
    width: 18rem;
    height: 20rem;
    max-width: 100%;
}

.emoji_picker,
.bottom_arrow {
    box-shadow: 0 0 5px 1px rgba(0, 0, 0, .0975);
}

.emoji_picker,
.picker_container {
    border-radius: 0.5rem;
    background: white;
}

.picker_container {
    position: relative;
    padding: 1rem;
    overflow: auto;
    z-index: 1;
}

.category {
    display: flex;
    flex-direction: column;
    margin-bottom: 1rem;
    color: rgb(169, 169, 169);
}

.emojis_container {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
}

.category button {
    margin: 0.5rem;
    margin-left: 0;
    background: inherit;
    border: none;
    font-size: 1.75rem;
    padding: 0;
}

.bottom_arrow {
    position: absolute;
    left: 50%;
    bottom: 0;
    width: 0.75rem;
    height: 0.75rem;
    transform: translate(-50%, 50%) rotate(45deg);
    background: white;
}
</style>
