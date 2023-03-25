import type {AxiosRequestConfig, AxiosResponse, InternalAxiosRequestConfig} from "axios";
import axios, {Axios} from "axios";
import Config from "@/libs/Config";
//import {STATUS_NEED_LOGIN} from "@/lib/StatusCode";
import {ElLoadingService, ElMessage} from "element-plus";
import type {LoadingInstance} from "element-plus/es/components/loading/src/loading";
import Player from "@/libs/Cache";

export class Http {
    public instance!: Axios;
    protected abortController!: AbortController;
    protected config: any = {
        loading: false,
        requesting: '',
    };

    constructor() {
        this.instance = axios.create({
            baseURL: Config.api,
            timeout: 60000,
            headers: {},
        });
        // 拦截器
        this.instance.interceptors.request.use(async (config: InternalAxiosRequestConfig) => {
            config.headers['token'] = Player.getToken();
            return config;
        });
        this.instance.interceptors.response.use((response: AxiosResponse) => {
            if (response.data.code < 0) {
                //ElMessage.error(response.data.message);
                return Promise.reject(response.data);
            }
            return response.data;
        });
    }

    public loading(v: any = true) {
        this.config.loading = v;
        return this;
    }

    public get(url: string, params: object | URLSearchParams | null = null) {
        return this.request({url, params});
    }

    public post(url: string, data?: object) {
        return this.request({
            url,
            method: "POST",
            data,
        });
    }

    public request(option: AxiosRequestConfig | string) {
        if (typeof option == "string") {
            option = {url: option,};
        }
        this.abortController = new AbortController();
        option.signal = this.abortController.signal;
        let loading: LoadingInstance;
        if (typeof this.config.loading == "string") {
            loading = ElLoadingService({text: this.config.loading});
        } else if (typeof this.config.loading == "boolean" && this.config.loading) {
            loading = ElLoadingService({text: '请稍候...'});
        }
        this.config.requesting = option.url;
        // @ts-ignore
        return this.instance.request(option).finally(() => this.reset()).finally(() => loading?.close());
    }

    public abort(url: string | null = null) {
        if (url === null || url === this.config.requesting) {
            this.abortController?.abort();
        }
        return this;
    }

    private reset() {
        this.config = {
            loading: false,
            requesting: '',
        };
    }
}

export default new Http();
