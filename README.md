# 📌 Discipline Apps
iOS: https://github.com/Wyatt-Grant/Discipline-Habit-Tracker-Android-App

Android: https://github.com/Wyatt-Grant/Discipline-Habit-Tracker-iOS-App

# 📌 Dsicipline API Documentation

This project exposes a set of RESTful API endpoints, organized by resource.  
All routes are prefixed with `/api` and most require authentication via **Laravel Sanctum**.

---

## 🔑 Authentication & User

| Method | Endpoint     | Middleware     | Description |
|--------|--------------|----------------|-------------|
| POST   | `/token`     | –              | Authenticate user and issue token |
| POST   | `/register`  | –              | Register a new user |
| GET    | `/user`      | `auth:sanctum` | Get the authenticated user |
| POST   | `/setAPN`    | `auth:sanctum` | Set Apple Push Notification token |

---

## 📂 Dynamics

| Method | Endpoint             | Middleware             | Description |
|--------|----------------------|------------------------|-------------|
| GET    | `/dynamic`           | `auth:sanctum`         | Get dynamic info |
| PUT    | `/dynamic/{dynamic}` | `auth:sanctum`, `owns.dynamic` | Update a dynamic |

---

## ✅ Tasks

| Method | Endpoint                        | Middleware             | Description |
|--------|---------------------------------|------------------------|-------------|
| GET    | `/tasks`                        | `auth:sanctum`         | Get all tasks |
| POST   | `/tasks`                        | `auth:sanctum`         | Create a new task |
| PUT    | `/task/{task}`                  | `auth:sanctum`, `owns.task` | Update a task |
| DELETE | `/task/{task}`                  | `auth:sanctum`, `owns.task` | Delete a task |
| POST   | `/complete-task/{task}`         | `auth:sanctum`, `owns.task` | Mark task as complete |
| POST   | `/uncomplete-task/{task}`       | `auth:sanctum`, `owns.task` | Mark task as incomplete |
| POST   | `/assign-group/{task}/{group}`  | `auth:sanctum`, `owns.task`, `owns.group` | Assign task to group |
| POST   | `/unassign-group/{task}/{group}`| `auth:sanctum`, `owns.task`, `owns.group` | Remove task from group |
| GET    | `/tasks/remaining`              | `auth:sanctum`         | Get daily remaining task count |
| GET    | `/tasks/reminders`              | `auth:sanctum`         | Get task reminders |
| POST   | `/complete-task-history/{taskHistory}`   | `auth:sanctum`, `owns.taskhistory` | Complete a task history entry |
| POST   | `/uncomplete-task-history/{taskHistory}` | `auth:sanctum`, `owns.taskhistory` | Undo completion of a task history entry |

---

## ⚖️ Punishments

| Method | Endpoint                          | Middleware             | Description |
|--------|-----------------------------------|------------------------|-------------|
| GET    | `/punishments`                    | `auth:sanctum`         | Get all punishments |
| POST   | `/punishments`                    | `auth:sanctum`         | Create a punishment |
| PUT    | `/punishment/{punishment}`        | `auth:sanctum`, `owns.punishment` | Update a punishment |
| DELETE | `/punishment/{punishment}`        | `auth:sanctum`, `owns.punishment` | Delete a punishment |
| POST   | `/add-punishment/{punishment}`    | `auth:sanctum`, `owns.punishment` | Increment punishment |
| POST   | `/remove-punishment/{punishment}` | `auth:sanctum`, `owns.punishment` | Decrement punishment |
| POST   | `/assign-punishment/{punishment}/{task}`   | `auth:sanctum`, `owns.punishment`, `owns.task` | Assign punishment to task |
| POST   | `/unassign-punishment/{punishment}/{task}` | `auth:sanctum`, `owns.punishment`, `owns.task` | Remove punishment from task |
| GET    | `/punishments/assigned`           | `auth:sanctum`         | Get total assigned punishments count |

---

## 🏆 Rewards

| Method | Endpoint                          | Middleware             | Description |
|--------|-----------------------------------|------------------------|-------------|
| GET    | `/rewards`                        | `auth:sanctum`         | Get all rewards |
| POST   | `/rewards`                        | `auth:sanctum`         | Create a reward |
| PUT    | `/reward/{reward}`                | `auth:sanctum`, `owns.reward` | Update a reward |
| DELETE | `/reward/{reward}`                | `auth:sanctum`, `owns.reward` | Delete a reward |
| POST   | `/add-reward/{reward}`            | `auth:sanctum`, `owns.reward` | Increment reward |
| POST   | `/remove-reward/{reward}`         | `auth:sanctum`, `owns.reward` | Decrement reward |
| POST   | `/assign-reward/{reward}/{task}`  | `auth:sanctum`, `owns.reward`, `owns.task` | Assign reward to task |
| POST   | `/unassign-reward/{reward}/{task}`| `auth:sanctum`, `owns.reward`, `owns.task` | Remove reward from task |
| GET    | `/points`                         | `auth:sanctum`         | Get current points |
| POST   | `/add-point`                      | `auth:sanctum`         | Add a point |
| POST   | `/remove-point`                   | `auth:sanctum`         | Remove a point |
| GET    | `/bank`                           | `auth:sanctum`         | Get banked reward count |

---

## 💬 Messages

| Method | Endpoint                          | Middleware             | Description |
|--------|-----------------------------------|------------------------|-------------|
| GET    | `/messages`                       | `auth:sanctum`         | Get all messages |
| POST   | `/messages`                       | `auth:sanctum`         | Create a message |
| PUT    | `/message/{message}`              | `auth:sanctum`, `owns.message` | Update a message |
| DELETE | `/message/{message}`              | `auth:sanctum`, `owns.message` | Delete a message |
| POST   | `/assign-message/{message}/{task}`| `auth:sanctum`, `owns.message`, `owns.task` | Assign message to task |
| POST   | `/unassign-message/{message}/{task}` | `auth:sanctum`, `owns.message`, `owns.task` | Remove message from task |

---

## 📜 Rules

| Method | Endpoint             | Middleware             | Description |
|--------|----------------------|------------------------|-------------|
| GET    | `/rules`             | `auth:sanctum`         | Get all rules |
| POST   | `/rules`             | `auth:sanctum`         | Create a rule |
| PUT    | `/rule/{rule}`       | `auth:sanctum`, `owns.rule` | Update a rule |
| DELETE | `/rule/{rule}`       | `auth:sanctum`, `owns.rule` | Delete a rule |

---

## 👥 Groups

| Method | Endpoint             | Middleware             | Description |
|--------|----------------------|------------------------|-------------|
| GET    | `/groups`            | `auth:sanctum`         | Get all groups |
| POST   | `/groups`            | `auth:sanctum`         | Create a group |
| POST   | `/sort-groups`       | `auth:sanctum`         | Update group sorting |
| PUT    | `/group/{group}`     | `auth:sanctum`, `owns.group` | Update a group |
| DELETE | `/group/{group}`     | `auth:sanctum`, `owns.group` | Delete a group |

---

## ⚙️ Middleware Notes

- **`auth:sanctum`** → Requires authentication via Laravel Sanctum token.  
- **`owns.*`** → Custom ownership checks (e.g., `owns.task`, `owns.reward`) ensure that only the owner of a resource can modify it.
