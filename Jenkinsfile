pipeline {
    agent any

    environment {
        DOCKER_HUB_USERNAME = credentials('dockerhub-username')
        DOCKER_HUB_PASSWORD = credentials('dockerhub-password')
        IMAGE_NAME = "ramaseck/laravel-app"
    }

    stages {
        stage('Checkout') {
            steps {
                git branch: 'main', url: 'https://github.com/Ramaseck1/laboAnalyse.git'
            }
        }

        stage('Install Dependencies') {
            steps {
                sh 'composer install'
            }
        }

        stage('Run Tests') {
            steps {
                sh 'php artisan test || true'
            }
        }

        stage('Build Docker Image') {
            steps {
                sh 'docker build -t $IMAGE_NAME:latest .'
            }
        }

        stage('Login Docker Hub') {
            steps {
                sh '''
                  echo $DOCKER_HUB_PASSWORD | docker login -u $DOCKER_HUB_USERNAME --password-stdin
                '''
            }
        }

        stage('Push Docker Image') {
            steps {
                sh 'docker push $IMAGE_NAME:latest'
            }
        }
    }
}
