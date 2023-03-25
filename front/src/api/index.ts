import {Http} from "@/libs/Http";

class Index extends Http {
    public roomList() {
        return this.get('/api/index/roomList');
    }

    public statistics() {
        return this.get('/api/index/statistics');
    }
}

export default new Index();
