# Makaira Connect Essential ![Version](https://img.shields.io/github/v/tag/MakairaIO/shopware-connect-essential?color=blue) [![Packagist Version](https://img.shields.io/packagist/v/makaira/shopware-connect-essential)](https://packagist.org/packages/makaira/shopware-connect-essential)

## 🎯 Purpose

The **Makaira Connect Essential** module serves as a bridge between Shopware and the Makaira platform. It provides functionality to manage and synchronize data between the two systems, ensuring that the persistence layer in Makaira is kept up-to-date with the latest information from Shopware. This module is essential for enabling seamless integration and efficient data handling for Shopware-based e-commerce platforms using Makaira.

### ✨ Key Features:
- 🔄 **Synchronization** of sales channel data with Makaira.
- 🛠️ **Management** of the Makaira persistence layer, including rebuilding, updating, and switching data.
- 🌐 **Support** for multiple sales channels with configurable credentials.
- 📡 **Continuous Updates**: The module listens to Shopware events via subscribers to ensure real-time updates for:
  - 🛍️ **Products**
  - 🗂️ **Categories**
  - 🏭 **Manufacturers**

---

## ⚙️ Installation

To install the **Makaira Connect Essential** module in your Shopware 6 environment, follow these steps:
1. **Install via Composer**:
   - Run the following command to require the plugin using Composer:
     ```bash
     composer require makaira/shopware-connect-essential
     ```

2. **Activate the Plugin**:
   - After installation, activate the plugin by running:
     ```bash
     bin/console plugin:install --activate MakairaConnectEssential
     ```
3. **Configure the Plugin**:
   - Navigate to the Shopware administration panel.
   - Go to **Settings > Plugins > Makaira Connect Essential**.
   - Enter the required Makaira API credentials for each sales channel.

4. **Trigger an Initial Update**:
   - After installation and configuration, trigger a full update of the Makaira persistence layer by running the following command:
     ```bash
     bin/console makaira:persistence-layer:update
     ```
   - This ensures that all existing data (products, categories, and manufacturers) is pushed to the Makaira platform.

---

## ⚙️ Configuration

The following configuration options are available for the **Makaira Connect Essential** module:

1. **Base URL of Makaira API**:
   - **Key**: `makairaBaseUrl`
   - **Description**: The base URL of the Makaira API.
   - **Default Value**: `https://<customer>.makaira.io`

2. **Makaira Shared Secret**:
   - **Key**: `makairaSharedSecret`
   - **Description**: The shared secret for authenticating with the Makaira API. This value must be set per sales channel.

3. **Makaira Customer**:
   - **Key**: `makairaCustomer`
   - **Description**: The customer identifier for the Makaira instance.

4. **Makaira Instance**:
   - **Key**: `makairaInstance`
   - **Description**: The instance name for Makaira (e.g., `live`). This value must be set per sales channel.

5. **API Timeout**:
   - **Key**: `apiTimeout`
   - **Description**: The timeout for API requests in seconds.
   - **Default Value**: `30`

### How to Configure

1. Navigate to the Shopware administration panel.
2. Go to **Settings > Plugins > Makaira Connect Essential**.
3. Fill in the required fields for each sales channel:
   - **Base URL of Makaira API**
   - **Makaira Shared Secret**
   - **Makaira Customer**
   - **Makaira Instance**
   - **API Timeout**
4. Save the configuration.

💡 **Note**: Ensure that the `Makaira Shared Secret` and `Makaira Instance` values are set individually for each sales channel to enable proper synchronization.

---

## 🛠️ Commands

The following commands allow you to manage the Makaira persistence layer effectively. They can be used to perform an initial full data push or to manually trigger updates and changes when needed. Once the initial setup is complete, the module ensures continuous synchronization through event subscribers, keeping your data up-to-date in real time.

### 🔧 Rebuild Command
- **Name**: `makaira:persistence-layer:rebuild`
- **Description**: Initialize rebuild of the Makaira persistence layer.
- **Arguments**:
  - `salesChannelId` (optional): The ID of the sales channel to rebuild. If not provided, all sales channels will be processed.

### 🔄 Update Command
- **Name**: `makaira:persistence-layer:update`
- **Description**: Push all data to the Makaira persistence layer.
- **Arguments**:
  - `salesChannelId` (optional): The ID of the sales channel to update. If not provided, all sales channels will be processed.

### 🔀 Switch Command
- **Name**: `makaira:persistence-layer:switch`
- **Description**: Use the rebuild data as active data for the Makaira persistence layer.
- **Arguments**:
  - `salesChannelId` (optional): The ID of the sales channel to switch. If not provided, all sales channels will be processed.

---

💡 **Tip**: Use these commands to manage your Makaira persistence layer efficiently and ensure your Shopware data stays in sync with Makaira!


## 🛠️ Development Setup

### Install
1. `git clone git@github.com:MakairaIO/shopware-connect-essential.git`
2. `make init`

### Usefull commands

- Start project: `make up`
- Stop project: `make down`
- SSH to container: `make ssh`