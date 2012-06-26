/*
 * Node.cpp
 *
 *  Created on: Jun 24, 2012
 *      Author: crackers
 */

#include "Client.h"
#include <iostream>

Client::Client(int id) {

	std::cout << "Creating Node " << id << "\n";
	char header[2048];
	int len = recv(id,(void*)header,sizeof(char)*2048,0);
	parseKey(&header[0],len);
	std::cout << key;
}

Client::~Client() {
	// TODO Auto-generated destructor stub
}

void Client::parseKey(char* header, int length) {
	char *field = strtok(header,"\r\n");
	char keyword[length];
	memset(&keyword, '\0', length*sizeof(char));
	this->key = new char[24];
	while(field!=NULL) {
		int i = 0;
		int j = 0;
		while(field[i]!=NULL) {
			if(field[i]==':') {
				i++;
				break;
			} else if(field[i] == ' ') {

			} else {
				keyword[j] = field[i];
				j++;
			}
			i++;
		}
		if(strcmp((char*)keyword, "Sec-WebSocket-Key")==0) {
			//The current token is the key
			int k = 0;
			while(field[i]!=NULL&&k<24) {
				if(field[i] == ' ') {

				} else {
					((char*)this->key)[k] = field[i];
					k++;
				}
				i++;
			}
			break;
		}
		memset(&keyword, '\0',length);
		field = strtok(NULL,"\r\n");
	}
}
