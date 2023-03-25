/**
 * 防抖
 * @param fn
 * @param wait
 */
export const debounce = (fn: Function, wait: number = 100): Function => {
    let timer: number | null = null;
    return function (...args: any[]): Promise<any> {
        if (timer !== null) {
            clearTimeout(timer);
        }
        // @ts-ignore
        const that = this;
        return new Promise((resolve) => {
            timer = setTimeout(() => resolve(fn.apply(that, args)), wait);
        });
    }
};
/**
 * 节流
 * @param fn
 * @param wait
 */
export const throttle = (fn: Function, wait = 200) => {
    let last = 0;
    let that = this;
    return async function (...args: any[]) {
        var now = new Date().getTime();
        if (now - last > wait) {
            last = new Date().getTime();
            return fn.apply(that, args);
        }
        return null;
    };
};
