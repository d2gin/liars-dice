import cookie from "js-cookie";

const key = 'icy8-dice-game-player-token';

export default {
    getToken() {
        return cookie.get(key);
    },
    setToken(token: any) {
        return cookie.set(key, token);
    },
    remove() {
        return cookie.remove(key);
    }
}
