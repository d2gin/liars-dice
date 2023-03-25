import {Http} from "@/libs/Http";
import store from "@/store";

class Player extends Http {
    public bind(nickname: string, sex: any) {
        return this.post('/api/player/bind', {
            nickname,
            sex,
            // @ts-ignore
            connection_id: store.state.Player.connectionId,
        });
    }

    public bindConnectionId(connection_id: any) {
        return this.post('/api/player/bindConnectionId', {connection_id});
    }

    public rollDice() {
        return this.post('/api/player/rollDice');
    }

    public detail() {
        return this.get('/api/player/detail');
    }
}

export default new Player();
