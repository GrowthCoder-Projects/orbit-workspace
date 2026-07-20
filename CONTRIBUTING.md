# Contributing to Orbit 🪐

Thank you for your interest in contributing to **Orbit**! We welcome contributions from developers, designers, and creators of all skill levels.

---

## 📜 Code of Conduct

Please be respectful, collaborative, and constructive in all issue discussions, pull requests, and community interactions.

---

## 🛠️ How Can You Contribute?

### 1. Reporting Bugs
- Check the [Issues tab](https://github.com/your-username/orbit/issues) to ensure the bug hasn't already been reported.
- Open a new issue with a clear title, description, steps to reproduce, and environment details.

### 2. Suggesting Features
- Open a feature request issue explaining the motivation, use case, and proposed design.

### 3. Submitting Pull Requests (PRs)
1. **Fork** the repository and create your feature branch:
   ```bash
   git checkout -b feature/my-amazing-feature
   ```
2. Make your changes adhering to our coding standards.
3. Run tests and verify code formatting:
   ```bash
   composer run ci:check
   ```
4. Commit your changes with descriptive messages.
5. Push to your branch and open a **Pull Request** targeting the `main` branch.

---

## 🎨 Coding Standards

- **PHP / Laravel**: Follow Pint guidelines. Run `composer run lint` to format code automatically.
- **Vue / TypeScript**: Follow ESLint & Prettier configurations. Run `npm run lint` and `npm run format`.
- **Testing**: Write Pest tests for new features and bug fixes. Ensure all tests pass (`php artisan test`).

---

Thank you for helping make Orbit better for everyone! 🚀
