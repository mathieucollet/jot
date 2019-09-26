<template>
    <div class="h-screen bg-white">
        <div class="flex">
            <left-nav-bar-component/>
            <div class="flex flex-col flex-1 h-screen overflow-y-hidden">
                <top-bar-component/>
                <div class="flex flex-col overflow-y-hidden flex-1">
                    <router-view class="p-6 overflow-x-hidden"></router-view>
                </div>
            </div>
        </div>


    </div>
</template>

<script>
    import LeftNavBarComponent from "./Layouts/LeftNavBarComponent";
    import TopBarComponent from "./Layouts/TopBarComponent";

    export default {
        components: {
            LeftNavBarComponent, TopBarComponent
        },
        name: "App",
        props: [
            'user'
        ],
        mounted() {
            window.axios.interceptors.request.use(
                (config) => {
                    config.data = {
                        ...config,
                        api_token: this.user.api_token
                    };
                    return config;
                }
            )
        }
    };
</script>

<style scoped>

</style>
