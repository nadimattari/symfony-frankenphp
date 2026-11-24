import type { Photo as PhotoDto } from "../types/api";
import type { FetchClient } from "../utils/fetch";
import { ApiService, apiClient } from "./api";

export type Photo = PhotoDto;

/** Demo-only fixture endpoint served by DemoDataController. */
export default class PhotoService extends ApiService<Photo> {
  constructor(client: FetchClient = apiClient) {
    super(client, "/demo/photos");
  }

  getImages(): Promise<Photo[]> {
    return this.list();
  }
}
