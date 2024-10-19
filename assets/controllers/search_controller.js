import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["output"]

    search(event) {
        const query = event.target.value;
        const url = this.data.get("url") + '?query=' + encodeURIComponent(query);

        fetch(url)
            .then(response => response.text())
            .then(html => {
                document.getElementById("book-list").innerHTML = html;
            })
            .catch(error => console.error('Error fetching books:', error));
    }
}
