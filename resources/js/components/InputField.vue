<template>
    <div class="relative pb-4">
        <label :for="name" class="text-blue-500 pt-2 uppercase text-xs font-bold absolute">{{ label }}</label>
        <input :id="name" type="text"
               class="pt-8 w-full border-b pb-2 focus:outline-none focus:border-blue-400 text-gray-900"
               :placeholder="placeholder" @input="updateField" v-model="value">
        <p class="text-red-600 text-sm" v-text="errorMessage(name)"></p>
    </div>
</template>

<script>
    export default {
        name: "InputField",
        props: [
            'name', 'label', 'placeholder', 'errors'
        ],
        data() {
            return {
                value: '',
            }
        },
        methods: {
            updateField() {
                this.$emit('update:field', this.value)
            },
            errorMessage(field) {
                if (this.errors && this.errors[field] && this.errors[field].length > 0) {
                    return this.errors[field][0]
                }
            }
        },
    }
</script>

<style scoped>

</style>
