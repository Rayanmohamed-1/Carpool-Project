CARPOOL-PROJECT
This is a client-server web application that is designed to help university students share rides to and from campus. The system will allow users to post or join rides, view available options, and communicate with other users, while the server handles ride matching and data management.

The application uses a simple but effective tech stack. The frontend is built with HTML and CSS, while the backend runs on PHP using an Apache server. A MySQL database is used to store key data such as user accounts, rides, bookings, and messages. On top of that, Docker and Docker Compose are used to containerise the whole system thus making it easier to run consistently across different machines without setup issues.

Deployment is done locally using Docker. After cloning the repository, the project folder must be opened in a terminal where the Docker Compose file is run to build and start both the web server and database containers. Once the containers are running, the system can then be accessed through a browser using localhost:8080. The containers can also be stopped when needed through Docker.



