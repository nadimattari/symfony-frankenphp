export interface Photo {
    itemImageSrc?: string;
    thumbnailImageSrc?: string;
    alt?: string;
    title?: string;
    [key: string]: unknown;
}

export default class PhotoService {
    getImages(): Promise<Photo[]> {
        return fetch('demo/data/photos.json')
            .then((res) => res.json())
            .then((d) => d.data as Photo[]);
    }
}
