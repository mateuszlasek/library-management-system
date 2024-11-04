import './bootstrap.js';
import { Application } from "@hotwired/stimulus";
import SearchController from "./controllers/search_controller";
import './styles/app.css';

const application = Application.start();
application.register("search", SearchController);
