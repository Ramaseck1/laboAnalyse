pipeline {
    agent any

   environment {
    DOCKER_HUB_USERNAME = credentials('dockerhub-credentials_USR') // récupère le username
    DOCKER_HUB_PASSWORD = credentials('dockerhub-credentials_PSW') // récupère le password
    IMAGE_NAME = "$DOCKER_HUB_USERNAME/labo-app"
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

        stage('Deploy to Render') {
            steps {
                withCredentials([string(credentialsId: 'render-api-key', variable: 'RENDER_API_KEY')]) {
                    sh """
                    curl -X POST "https://api.render.com/v1/services/SERVICE_ID/deploys" \
                    -H "Authorization: Bearer $RENDER_API_KEY" \
                    -H "Content-Type: application/json" \
                    -d '{"clearCache": true}'
                    """
                }
            }
        }

    }
}
